<?php

final class Package extends Model
{
    private const WRITABLE_FIELDS = [
        'name', 'slug', 'category', 'duration_days', 'regular_price', 'offer_price',
        'discount_amount', 'discount_percentage', 'offer_start_date', 'offer_end_date',
        'offer_enabled', 'badge', 'image', 'description',
        'includes_trainer', 'includes_locker', 'includes_steam', 'includes_sauna', 'includes_diet_plan',
        'is_featured', 'is_active', 'sort_order',
    ];

    public const BADGES = ['BEST VALUE', 'POPULAR', 'HOT', 'LIMITED OFFER', 'SALE', 'NEW', 'RECOMMENDED'];

    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM membership_packages WHERE is_active = 1 ORDER BY sort_order ASC');
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM membership_packages WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $pkg = $stmt->fetch();
        return $pkg ? $this->withComputedOffer($pkg) : null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM membership_packages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $pkg = $stmt->fetch();
        return $pkg ? $this->withComputedOffer($pkg) : null;
    }

    public function allForAdmin(): array
    {
        $stmt = $this->db->query('SELECT * FROM membership_packages ORDER BY sort_order ASC');
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    /**
     * Adds 'offer_is_live', 'display_price', 'savings_amount', 'savings_percentage'
     * computed fresh from offer_enabled + the date window — this (not just the
     * offer_enabled flag) is what makes an expired offer automatically fall back
     * to the regular price with no admin action required.
     */
    private function withComputedOffer(array $pkg): array
    {
        $now = new DateTimeImmutable();
        $startOk = empty($pkg['offer_start_date']) || new DateTimeImmutable($pkg['offer_start_date']) <= $now;
        $endOk = empty($pkg['offer_end_date']) || new DateTimeImmutable($pkg['offer_end_date']) > $now;

        $isLive = (bool) $pkg['offer_enabled'] && !empty($pkg['offer_price']) && $startOk && $endOk;

        $pkg['offer_is_live'] = $isLive;
        $pkg['display_price'] = $isLive ? $pkg['offer_price'] : $pkg['regular_price'];
        $pkg['savings_amount'] = $isLive ? $pkg['discount_amount'] : null;
        $pkg['savings_percentage'] = $isLive ? $pkg['discount_percentage'] : null;

        return $pkg;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM membership_packages WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Returns an error message if the offer price is invalid, or null if OK. */
    public function validateOfferPrice(?float $regularPrice, ?float $offerPrice): ?string
    {
        if ($offerPrice === null) {
            return null;
        }
        if ($regularPrice === null || $offerPrice >= $regularPrice) {
            return 'Offer price must be lower than the regular price.';
        }
        if ($offerPrice <= 0) {
            return 'Offer price must be greater than zero.';
        }
        return null;
    }

    private function applyDiscountComputation(array $data): array
    {
        if (!array_key_exists('regular_price', $data)) {
            return $data;
        }

        $regular = isset($data['regular_price']) ? (float) $data['regular_price'] : null;
        $offer = isset($data['offer_price']) && $data['offer_price'] !== '' ? (float) $data['offer_price'] : null;

        if ($offer !== null && $regular !== null) {
            $data['discount_amount'] = round($regular - $offer, 2);
            $data['discount_percentage'] = round((($regular - $offer) / $regular) * 100, 2);
        } else {
            $data['discount_amount'] = null;
            $data['discount_percentage'] = null;
            $data['offer_price'] = null;
        }

        return $data;
    }

    public function create(array $data): int
    {
        $data = $this->applyDiscountComputation($data);
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $columns = array_keys($fields);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $stmt = $this->db->prepare(
            'INSERT INTO membership_packages (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($fields);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data = $this->applyDiscountComputation($data);
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE membership_packages SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM membership_packages WHERE id = :id')->execute(['id' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE membership_packages SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    public function toggleFeatured(int $id): void
    {
        $this->db->prepare('UPDATE membership_packages SET is_featured = NOT is_featured WHERE id = :id')->execute(['id' => $id]);
    }

    public function toggleOfferEnabled(int $id): void
    {
        $this->db->prepare('UPDATE membership_packages SET offer_enabled = NOT offer_enabled WHERE id = :id')->execute(['id' => $id]);
    }

    /** Swaps sort_order with the adjacent package in the given direction (mirrors Trainer::move()). */
    public function move(int $id, string $direction): void
    {
        $pkg = $this->find($id);
        if (!$pkg) {
            return;
        }

        $comparator = $direction === 'up' ? '<' : '>';
        $orderBy = $direction === 'up' ? 'sort_order DESC, id DESC' : 'sort_order ASC, id ASC';

        $stmt = $this->db->prepare(
            "SELECT * FROM membership_packages WHERE sort_order $comparator :order OR (sort_order = :order2 AND id " . ($direction === 'up' ? '<' : '>') . " :id)
             ORDER BY $orderBy LIMIT 1"
        );
        $stmt->execute(['order' => $pkg['sort_order'], 'order2' => $pkg['sort_order'], 'id' => $id]);
        $neighbor = $stmt->fetch();

        if (!$neighbor) {
            return;
        }

        $this->db->prepare('UPDATE membership_packages SET sort_order = :order WHERE id = :id')
            ->execute(['order' => $neighbor['sort_order'], 'id' => $pkg['id']]);
        $this->db->prepare('UPDATE membership_packages SET sort_order = :order WHERE id = :id')
            ->execute(['order' => $pkg['sort_order'], 'id' => $neighbor['id']]);
    }
}
