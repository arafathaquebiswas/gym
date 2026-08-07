<?php

final class BlogPost extends Model
{
    /** Matches the blog_posts.category enum — the admin form and public filter both read this. */
    public const CATEGORIES = [
        'workout_tips' => 'Workout Tips',
        'diet_tips' => 'Diet Tips',
        'fitness_news' => 'Fitness News',
        'announcements' => 'Announcements',
    ];

    /**
     * 'views' and 'created_at' are deliberately absent: view counts belong to
     * incrementViews(), and a post's creation time is not editable.
     */
    private const WRITABLE_FIELDS = [
        'author_id', 'title', 'slug', 'category', 'excerpt',
        'content', 'featured_image', 'status', 'published_at',
    ];

    public function paginate(int $page, int $perPage, ?string $category = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ["status = 'published'"];
        $params = [];
        if ($category) {
            $where[] = 'category = :category';
            $params['category'] = $category;
        }
        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM blog_posts WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT * FROM blog_posts WHERE $whereSql ORDER BY published_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM blog_posts WHERE slug = :slug AND status = 'published' LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $post = $stmt->fetch();
        return $post ?: null;
    }

    /** Admin lookup — unlike findBySlug(), a draft must be reachable so it can be edited. */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** @param array{search?:string,status?:string,category?:string} $filters */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 15): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE :search OR p.excerpt LIKE :search2)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }
        foreach (['status', 'category'] as $column) {
            if (!empty($filters[$column])) {
                $where[] = "p.$column = :$column";
                $params[$column] = $filters[$column];
            }
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM blog_posts p' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $stmt = $this->db->prepare(
            'SELECT p.*, u.name AS author_name
             FROM blog_posts p
             LEFT JOIN users u ON u.id = p.author_id' . $whereSql . '
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM blog_posts WHERE slug = :slug';
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

        $stmt = $this->db->prepare(
            'INSERT INTO blog_posts (' . implode(', ', $columns) . ', created_at)
             VALUES (:' . implode(', :', $columns) . ', NOW())'
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

        $stmt = $this->db->prepare("UPDATE blog_posts SET $set WHERE id = :id");
        $stmt->execute($fields);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM blog_posts WHERE id = :id')->execute(['id' => $id]);
    }

    public function incrementViews(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE blog_posts SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function recent(int $limit = 3): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
