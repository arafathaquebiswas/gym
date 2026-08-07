<?php

/**
 * Cart identity: a logged-in shopper's cart is keyed by user_id; a guest's
 * cart is keyed by the current PHP session_id() (cart_token) — no separate
 * cart cookie, reuses the session already started for every request.
 */
final class Cart extends Model
{
    /** @return array{user_id:?int,cart_token:?string} */
    public static function identity(): array
    {
        return Auth::check()
            ? ['user_id' => (int) Auth::user()['id'], 'cart_token' => null]
            : ['user_id' => null, 'cart_token' => session_id()];
    }

    /**
     * Line items joined with live product data — prices/stock are never trusted from
     * stale cart rows. Where a variant was chosen, its price and stock override the
     * product's, since that is what the shopper actually picked.
     */
    public function forIdentity(?int $userId, ?string $cartToken): array
    {
        [$where, $params] = $this->identityClause($userId, $cartToken, 'c.');
        $stmt = $this->db->prepare(
            "SELECT c.id AS cart_id, c.qty, c.variant_id, p.*,
                    v.price AS variant_price, v.offer_price AS variant_offer_price,
                    v.stock_qty AS variant_stock, v.weight AS variant_weight,
                    v.sku AS variant_sku, v.status AS variant_status
             FROM shopping_cart c
             JOIN products p ON p.id = c.product_id
             LEFT JOIN product_variants v ON v.id = c.variant_id
             WHERE $where ORDER BY c.id ASC"
        );
        $stmt->execute($params);

        $lines = [];
        foreach ($stmt->fetchAll() as $line) {
            $lines[] = self::applyVariant($line);
        }

        return $lines;
    }

    /**
     * Folds a chosen variant's price, stock and label over the parent product's, so
     * every consumer (cart page, checkout, order builder) reads one consistent shape
     * and does not have to know whether a variant was involved.
     */
    public static function applyVariant(array $line): array
    {
        $line['variant_id'] = (int) ($line['variant_id'] ?? 0);
        $line['variant_label'] = null;

        if ($line['variant_id'] <= 0 || ($line['variant_status'] ?? null) === null) {
            return $line;
        }

        $line['variant_label'] = ProductVariant::label([
            'weight' => $line['variant_weight'] ?? null,
            'sku' => $line['variant_sku'] ?? null,
        ]);

        if (($line['variant_price'] ?? null) !== null) {
            $line['selling_price'] = (float) $line['variant_price'];
        }
        if (($line['variant_offer_price'] ?? null) !== null) {
            $line['offer_price'] = (float) $line['variant_offer_price'];
        }
        if (($line['variant_stock'] ?? null) !== null) {
            $line['stock_qty'] = (int) $line['variant_stock'];
        }
        if (!empty($line['variant_sku'])) {
            $line['sku'] = $line['variant_sku'];
        }

        return $line;
    }

    public function count(?int $userId, ?string $cartToken): int
    {
        [$where, $params] = $this->identityClause($userId, $cartToken, 'c.');
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(qty), 0) FROM shopping_cart c WHERE $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** $variantId 0 means the plain product. The same product in two weights is two lines. */
    public function add(?int $userId, ?string $cartToken, int $productId, int $qty, int $variantId = 0): void
    {
        // Look up then write, rather than an upsert: shopping_cart carries two
        // unique indexes — (user_id, product_id, variant_id) and the cart_token
        // equivalent — and a row belongs to either the signed-in user or the guest
        // token, so only one of them can ever be the conflict. MySQL infers that on
        // its own but SQLite's ON CONFLICT needs a single target named up front,
        // and this keeps one code path for both engines.
        $ownerColumn = $userId !== null ? 'user_id' : 'cart_token';
        $find = $this->db->prepare(
            "SELECT id FROM shopping_cart
             WHERE $ownerColumn = :owner AND product_id = :product_id AND variant_id = :variant_id LIMIT 1"
        );
        $find->execute([
            'owner' => $userId ?? $cartToken,
            'product_id' => $productId,
            'variant_id' => $variantId,
        ]);
        $existingId = $find->fetchColumn();

        if ($existingId !== false) {
            $update = $this->db->prepare('UPDATE shopping_cart SET qty = qty + :qty WHERE id = :id');
            $update->execute(['qty' => $qty, 'id' => (int) $existingId]);
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO shopping_cart (user_id, cart_token, product_id, variant_id, qty)
             VALUES (:user_id, :cart_token, :product_id, :variant_id, :qty)'
        );
        $insert->execute([
            'user_id' => $userId, 'cart_token' => $cartToken,
            'product_id' => $productId, 'variant_id' => $variantId, 'qty' => $qty,
        ]);
    }

    /**
     * Addressed by cart row id, not product id: with variants a product can occupy
     * several lines, so a product id no longer identifies one of them. The identity
     * clause still scopes the write to the caller's own cart.
     */
    public function updateQty(?int $userId, ?string $cartToken, int $cartId, int $qty): void
    {
        [$where, $params] = $this->identityClause($userId, $cartToken);
        $params['cart_id'] = $cartId;
        $params['qty'] = $qty;
        $this->db->prepare("UPDATE shopping_cart SET qty = :qty WHERE $where AND id = :cart_id")->execute($params);
    }

    public function remove(?int $userId, ?string $cartToken, int $cartId): void
    {
        [$where, $params] = $this->identityClause($userId, $cartToken);
        $params['cart_id'] = $cartId;
        $this->db->prepare("DELETE FROM shopping_cart WHERE $where AND id = :cart_id")->execute($params);
    }

    /** One line, scoped to the caller's cart — used to validate a qty change against live stock. */
    public function findLine(?int $userId, ?string $cartToken, int $cartId): ?array
    {
        foreach ($this->forIdentity($userId, $cartToken) as $line) {
            if ((int) $line['cart_id'] === $cartId) {
                return $line;
            }
        }
        return null;
    }

    public function clear(?int $userId, ?string $cartToken): void
    {
        [$where, $params] = $this->identityClause($userId, $cartToken);
        $this->db->prepare("DELETE FROM shopping_cart WHERE $where")->execute($params);
    }

    /** Called right after login so an anonymous cart isn't lost when a shopper signs in mid-session. */
    public function mergeGuestIntoUser(string $cartToken, int $userId): void
    {
        $guestLines = $this->forIdentity(null, $cartToken);
        foreach ($guestLines as $line) {
            $this->add($userId, null, (int) $line['id'], (int) $line['qty'], (int) $line['variant_id']);
        }
        $this->clear(null, $cartToken);
    }

    /** $prefix is only needed by the JOIN-based queries (forIdentity/count) — plain UPDATE/DELETE have no alias. */
    private function identityClause(?int $userId, ?string $cartToken, string $prefix = ''): array
    {
        if ($userId !== null) {
            return [$prefix . 'user_id = :user_id', ['user_id' => $userId]];
        }
        return [$prefix . 'cart_token = :cart_token', ['cart_token' => $cartToken]];
    }
}
