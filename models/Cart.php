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

    /** Line items joined with live product data — prices/stock are never trusted from stale cart rows. */
    public function forIdentity(?int $userId, ?string $cartToken): array
    {
        [$where, $params] = $this->identityClause($userId, $cartToken, 'c.');
        $stmt = $this->db->prepare(
            "SELECT c.id AS cart_id, c.qty, p.* FROM shopping_cart c
             JOIN products p ON p.id = c.product_id
             WHERE $where ORDER BY c.id ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(?int $userId, ?string $cartToken): int
    {
        [$where, $params] = $this->identityClause($userId, $cartToken, 'c.');
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(qty), 0) FROM shopping_cart c WHERE $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function add(?int $userId, ?string $cartToken, int $productId, int $qty): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO shopping_cart (user_id, cart_token, product_id, qty) VALUES (:user_id, :cart_token, :product_id, :qty)
             ON DUPLICATE KEY UPDATE qty = qty + :qty2'
        );
        $stmt->execute([
            'user_id' => $userId, 'cart_token' => $cartToken, 'product_id' => $productId,
            'qty' => $qty, 'qty2' => $qty,
        ]);
    }

    public function updateQty(?int $userId, ?string $cartToken, int $productId, int $qty): void
    {
        [$where, $params] = $this->identityClause($userId, $cartToken);
        $params['product_id'] = $productId;
        $params['qty'] = $qty;
        $this->db->prepare("UPDATE shopping_cart SET qty = :qty WHERE $where AND product_id = :product_id")->execute($params);
    }

    public function remove(?int $userId, ?string $cartToken, int $productId): void
    {
        [$where, $params] = $this->identityClause($userId, $cartToken);
        $params['product_id'] = $productId;
        $this->db->prepare("DELETE FROM shopping_cart WHERE $where AND product_id = :product_id")->execute($params);
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
            $this->add($userId, null, (int) $line['id'], (int) $line['qty']);
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
