<?php

final class Attendance extends Model
{
    public function checkIn(int $memberId, ?int $recordedBy): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO attendance (member_id, check_in, method, recorded_by)
             VALUES (:member_id, NOW(), "manual", :recorded_by)'
        );
        $stmt->execute(['member_id' => $memberId, 'recorded_by' => $recordedBy]);
        return (int) $this->db->lastInsertId();
    }

    public function checkOut(int $attendanceId): void
    {
        $stmt = $this->db->prepare('UPDATE attendance SET check_out = NOW() WHERE id = :id AND check_out IS NULL');
        $stmt->execute(['id' => $attendanceId]);
    }

    public function openSessionForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM attendance WHERE member_id = :member_id AND check_out IS NULL
             ORDER BY check_in DESC LIMIT 1'
        );
        $stmt->execute(['member_id' => $memberId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function recentForMember(int $memberId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM attendance WHERE member_id = :member_id ORDER BY check_in DESC LIMIT :limit'
        );
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
