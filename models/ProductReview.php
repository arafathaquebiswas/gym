<?php

final class ProductReview extends Model
{
    /** Public-facing — only what's been moderated as approved. */
    public function forProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.name AS member_name
             FROM product_reviews r
             JOIN members m ON m.id = r.member_id
             JOIN users u ON u.id = m.user_id
             WHERE r.product_id = :product_id AND r.status = 'approved'
             ORDER BY r.created_at DESC"
        );
        $stmt->execute(['product_id' => $productId]);
        return array_map([$this, 'withPhotos'], $stmt->fetchAll());
    }

    /** Only counts approved reviews — matches what forProduct() shows publicly. */
    public function averageRating(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS review_count, ROUND(AVG(rating), 1) AS average
             FROM product_reviews WHERE product_id = :product_id AND status = 'approved'"
        );
        $stmt->execute(['product_id' => $productId]);
        $row = $stmt->fetch();
        return [
            'count' => (int) ($row['review_count'] ?? 0),
            'average' => $row['average'] !== null ? (float) $row['average'] : 0.0,
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, p.name AS product_name, u.name AS member_name
             FROM product_reviews r
             JOIN products p ON p.id = r.product_id
             JOIN members m ON m.id = r.member_id
             JOIN users u ON u.id = m.user_id
             WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $review = $stmt->fetch();
        return $review ? $this->withPhotos($review) : null;
    }

    /** @param array{rating?:string,status?:string,product_id?:string,member_id?:string,sort?:string} $filters */
    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['rating'])) {
            $where[] = 'r.rating = :rating';
            $params['rating'] = $filters['rating'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['product_id'])) {
            $where[] = 'r.product_id = :product_id';
            $params['product_id'] = $filters['product_id'];
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $joins = 'FROM product_reviews r
                  JOIN products p ON p.id = r.product_id
                  JOIN members m ON m.id = r.member_id
                  JOIN users u ON u.id = m.user_id';

        $countStmt = $this->db->prepare("SELECT COUNT(*) $joins" . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $orderBy = ($filters['sort'] ?? '') === 'oldest' ? 'r.created_at ASC' : 'r.created_at DESC';

        $stmt = $this->db->prepare(
            "SELECT r.*, p.name AS product_name, u.name AS member_name $joins" . $whereSql
            . " ORDER BY $orderBy LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page,
            'perPage' => $perPage, 'totalPages' => $totalPages,
        ];
    }

    public function pendingCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'");
        return (int) $stmt->fetchColumn();
    }

    public function hasReviewed(int $memberId, int $productId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM product_reviews WHERE member_id = :member_id AND product_id = :product_id');
        $stmt->execute(['member_id' => $memberId, 'product_id' => $productId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** A member may review a product only if they've actually bought it — online (delivered) or in-store (POS). */
    public function hasPurchased(int $memberId, int $productId): bool
    {
        $orderStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = (SELECT user_id FROM members WHERE id = :member_id)
               AND oi.product_id = :product_id AND o.status = 'delivered'"
        );
        $orderStmt->execute(['member_id' => $memberId, 'product_id' => $productId]);
        if ((int) $orderStmt->fetchColumn() > 0) {
            return true;
        }

        $saleStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM sales s
             JOIN sale_items si ON si.sale_id = s.id
             WHERE s.member_id = :member_id AND si.product_id = :product_id'
        );
        $saleStmt->execute(['member_id' => $memberId, 'product_id' => $productId]);
        return (int) $saleStmt->fetchColumn() > 0;
    }

    public function canReview(int $memberId, int $productId): bool
    {
        return !$this->hasReviewed($memberId, $productId) && $this->hasPurchased($memberId, $productId);
    }

    /** New reviews always start pending — they only appear publicly once an admin approves them. */
    public function create(int $productId, int $memberId, ?int $orderId, int $rating, string $comment): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_reviews (product_id, member_id, order_id, rating, comment, status)
             VALUES (:product_id, :member_id, :order_id, :rating, :comment, "pending")'
        );
        $stmt->execute([
            'product_id' => $productId, 'member_id' => $memberId, 'order_id' => $orderId,
            'rating' => $rating, 'comment' => $comment,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->prepare('UPDATE product_reviews SET status = :status WHERE id = :id')->execute(['status' => $status, 'id' => $id]);
    }

    public function reply(int $id, string $reply): void
    {
        $this->db->prepare('UPDATE product_reviews SET admin_reply = :reply, replied_at = NOW() WHERE id = :id')
            ->execute(['reply' => $reply, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM product_reviews WHERE id = :id')->execute(['id' => $id]);
    }

    private function withPhotos(array $review): array
    {
        $review['photos'] = (new ProductReviewPhoto())->forReview((int) $review['id']);
        return $review;
    }
}
