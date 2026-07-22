<?php

final class BlogPost extends Model
{
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
