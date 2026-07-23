<?php

final class Trainer extends Model
{
    /** Fields the admin form is allowed to write. */
    private const WRITABLE_FIELDS = [
        'name', 'slug', 'job_title', 'gender', 'phone', 'email', 'dob', 'joining_date',
        'specialization', 'experience_years', 'certifications', 'achievements', 'languages_spoken',
        'monthly_pt_price', 'hourly_rate', 'offer_price', 'offer_enabled', 'offer_start_date', 'offer_end_date',
        'max_members', 'availability_status', 'bio',
        'photo', 'cover_photo', 'facebook_url', 'instagram_url', 'linkedin_url',
        'display_order', 'is_featured', 'is_active',
    ];

    public function allActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM trainers WHERE is_active = 1 ORDER BY display_order ASC, name ASC"
        );
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM trainers WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $trainer = $stmt->fetch();
        return $trainer ? $this->withComputedOffer($trainer) : null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM trainers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $trainer = $stmt->fetch();
        return $trainer ? $this->withComputedOffer($trainer) : null;
    }

    /**
     * Ported from Product::withComputedOffer() — same live-offer-window logic.
     * Unlike Package, Trainer has no separate discount_amount/discount_percentage columns,
     * so savings are derived directly from monthly_pt_price vs offer_price.
     */
    public function withComputedOffer(array $trainer): array
    {
        $now = new DateTimeImmutable();
        $startOk = empty($trainer['offer_start_date']) || new DateTimeImmutable($trainer['offer_start_date']) <= $now;
        $endOk = empty($trainer['offer_end_date']) || new DateTimeImmutable($trainer['offer_end_date']) > $now;

        $isLive = (bool) $trainer['offer_enabled'] && !empty($trainer['offer_price']) && $startOk && $endOk;

        $trainer['offer_is_live'] = $isLive;
        $trainer['display_price'] = $isLive ? $trainer['offer_price'] : $trainer['monthly_pt_price'];

        if ($isLive && (float) $trainer['monthly_pt_price'] > 0) {
            $trainer['savings_amount'] = round((float) $trainer['monthly_pt_price'] - (float) $trainer['offer_price'], 2);
            $trainer['savings_percentage'] = round(100 * $trainer['savings_amount'] / (float) $trainer['monthly_pt_price']);
        } else {
            $trainer['savings_amount'] = null;
            $trainer['savings_percentage'] = null;
        }

        return $trainer;
    }

    /**
     * @param array{search?:string,status?:string,specialization?:string,sort?:string} $filters
     */
    public function allForAdmin(array $filters = []): array
    {
        [$where, $params] = $this->buildFilterClause($filters);

        $sql = 'SELECT * FROM trainers';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY ' . $this->sortClause($filters['sort'] ?? '');

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'withComputedOffer'], $stmt->fetchAll());
    }

    /**
     * Same filters as allForAdmin(), but LIMIT/OFFSET-paginated for the admin list.
     * @param array{search?:string,status?:string,specialization?:string,sort?:string} $filters
     * @return array{items:array, total:int, page:int, perPage:int, totalPages:int}
     */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 15): array
    {
        [$where, $params] = $this->buildFilterClause($filters);
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM trainers' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT * FROM trainers' . $whereSql . ' ORDER BY ' . $this->sortClause($filters['sort'] ?? '')
            . ' LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map([$this, 'withComputedOffer'], $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /** Aggregate counts for the admin list's statistics strip. */
    public function adminStatistics(): array
    {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(is_active = 1) AS active,
                SUM(is_featured = 1) AS featured,
                SUM(availability_status = 'available') AS available,
                ROUND(AVG(NULLIF(experience_years, 0)), 1) AS avg_experience
             FROM trainers"
        );
        $row = $stmt->fetch();
        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'featured' => (int) ($row['featured'] ?? 0),
            'available' => (int) ($row['available'] ?? 0),
            'avgExperience' => $row['avg_experience'] !== null ? (float) $row['avg_experience'] : 0.0,
        ];
    }

    private function buildFilterClause(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = 'name LIKE :search';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'availability_status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['specialization'])) {
            $where[] = 'specialization = :specialization';
            $params['specialization'] = $filters['specialization'];
        }

        return [$where, $params];
    }

    private function sortClause(string $sort): string
    {
        $sortMap = [
            'experience' => 'experience_years DESC',
            'price' => 'monthly_pt_price DESC',
            'order' => 'display_order ASC',
        ];
        return $sortMap[$sort] ?? 'display_order ASC, name ASC';
    }

    public function distinctSpecializations(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT specialization FROM trainers WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization ASC"
        );
        return array_column($stmt->fetchAll(), 'specialization');
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM trainers WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        $columns = array_keys($fields);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);

        $stmt = $this->db->prepare(
            'INSERT INTO trainers (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($fields);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = array_intersect_key($data, array_flip(self::WRITABLE_FIELDS));
        if (!$fields) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($fields)));
        $fields['id'] = $id;

        $stmt = $this->db->prepare("UPDATE trainers SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM trainers WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare('UPDATE trainers SET is_active = NOT is_active WHERE id = :id')->execute(['id' => $id]);
    }

    public function toggleFeatured(int $id): void
    {
        $this->db->prepare('UPDATE trainers SET is_featured = NOT is_featured WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * Swaps display_order with the adjacent trainer in the given direction.
     */
    public function move(int $id, string $direction): void
    {
        $trainer = $this->find($id);
        if (!$trainer) {
            return;
        }

        $comparator = $direction === 'up' ? '<' : '>';
        $orderBy = $direction === 'up' ? 'display_order DESC, id DESC' : 'display_order ASC, id ASC';

        $stmt = $this->db->prepare(
            "SELECT * FROM trainers WHERE display_order $comparator :order OR (display_order = :order2 AND id " . ($direction === 'up' ? '<' : '>') . " :id)
             ORDER BY $orderBy LIMIT 1"
        );
        $stmt->execute(['order' => $trainer['display_order'], 'order2' => $trainer['display_order'], 'id' => $id]);
        $neighbor = $stmt->fetch();

        if (!$neighbor) {
            return;
        }

        $this->db->prepare('UPDATE trainers SET display_order = :order WHERE id = :id')
            ->execute(['order' => $neighbor['display_order'], 'id' => $trainer['id']]);
        $this->db->prepare('UPDATE trainers SET display_order = :order WHERE id = :id')
            ->execute(['order' => $trainer['display_order'], 'id' => $neighbor['id']]);
    }

    /** Ported from Product::validateOfferPrice() — same rule, different table. */
    public function validateOfferPrice(?float $regularPrice, ?float $offerPrice): ?string
    {
        if ($offerPrice === null) {
            return null;
        }
        if ($regularPrice === null || $offerPrice >= $regularPrice) {
            return 'Offer price must be lower than the monthly price.';
        }
        if ($offerPrice <= 0) {
            return 'Offer price must be greater than zero.';
        }
        return null;
    }

    /** Distinct members with a confirmed, upcoming booking for this trainer. */
    public function assignedMemberCount(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT member_id) FROM trainer_booking
             WHERE trainer_id = :id AND status = 'confirmed' AND booking_date >= CURDATE()"
        );
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
