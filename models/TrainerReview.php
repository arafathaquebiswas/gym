<?php

final class TrainerReview extends Model
{
    public function forTrainer(int $trainerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, u.name AS member_name FROM trainer_reviews r
             JOIN members m ON m.id = r.member_id
             JOIN users u ON u.id = m.user_id
             WHERE r.trainer_id = :trainer_id ORDER BY r.created_at DESC'
        );
        $stmt->execute(['trainer_id' => $trainerId]);
        return $stmt->fetchAll();
    }

    public function averageRating(int $trainerId): ?float
    {
        $stmt = $this->db->prepare('SELECT AVG(rating) FROM trainer_reviews WHERE trainer_id = :trainer_id');
        $stmt->execute(['trainer_id' => $trainerId]);
        $avg = $stmt->fetchColumn();
        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function count(int $trainerId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM trainer_reviews WHERE trainer_id = :trainer_id');
        $stmt->execute(['trainer_id' => $trainerId]);
        return (int) $stmt->fetchColumn();
    }

    /** A member can review a trainer only if they've booked a session with them, and haven't already reviewed. */
    public function canReview(int $trainerId, int $memberId): bool
    {
        $hasBooking = $this->db->prepare(
            'SELECT COUNT(*) FROM trainer_booking WHERE trainer_id = :trainer_id AND member_id = :member_id'
        );
        $hasBooking->execute(['trainer_id' => $trainerId, 'member_id' => $memberId]);
        if ((int) $hasBooking->fetchColumn() === 0) {
            return false;
        }

        $hasReview = $this->db->prepare(
            'SELECT COUNT(*) FROM trainer_reviews WHERE trainer_id = :trainer_id AND member_id = :member_id'
        );
        $hasReview->execute(['trainer_id' => $trainerId, 'member_id' => $memberId]);
        return (int) $hasReview->fetchColumn() === 0;
    }

    public function submit(int $trainerId, int $memberId, int $rating, ?string $comment): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO trainer_reviews (trainer_id, member_id, rating, comment, created_at) VALUES (:trainer_id, :member_id, :rating, :comment, NOW())'
        );
        $stmt->execute([
            'trainer_id' => $trainerId,
            'member_id' => $memberId,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }
}
