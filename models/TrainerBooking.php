<?php

final class TrainerBooking extends Model
{
    private const SLOT_LENGTH_HOURS = 1;

    /**
     * Open 1-hour slots for a trainer on a given date: the day's schedule
     * window, minus already-confirmed bookings and past times (if today).
     *
     * @return array<int, array{start:string,end:string,label:string}>
     */
    public function openSlotsFor(int $trainerId, string $date): array
    {
        $dayOfWeek = (int) date('w', strtotime($date));

        $scheduleStmt = $this->db->prepare(
            'SELECT * FROM trainer_schedule WHERE trainer_id = :trainer_id AND day_of_week = :day LIMIT 1'
        );
        $scheduleStmt->execute(['trainer_id' => $trainerId, 'day' => $dayOfWeek]);
        $schedule = $scheduleStmt->fetch();

        if (!$schedule || $schedule['is_off'] || !$schedule['start_time'] || !$schedule['end_time']) {
            return [];
        }

        $takenStmt = $this->db->prepare(
            "SELECT start_time FROM trainer_booking
             WHERE trainer_id = :trainer_id AND booking_date = :date AND status = 'confirmed'"
        );
        $takenStmt->execute(['trainer_id' => $trainerId, 'date' => $date]);
        $taken = array_column($takenStmt->fetchAll(), 'start_time');

        $slots = [];
        $cursor = strtotime($schedule['start_time']);
        $end = strtotime($schedule['end_time']);
        $isToday = $date === date('Y-m-d');
        $now = time();

        while ($cursor + self::SLOT_LENGTH_HOURS * 3600 <= $end) {
            $slotStart = date('H:i:s', $cursor);
            $slotEndTs = $cursor + self::SLOT_LENGTH_HOURS * 3600;
            $slotEnd = date('H:i:s', $slotEndTs);

            $isPast = $isToday && strtotime($date . ' ' . $slotStart) <= $now;

            if (!in_array($slotStart, $taken, true) && !$isPast) {
                $slots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'label' => date('g:i A', $cursor) . ' - ' . date('g:i A', $slotEndTs),
                ];
            }
            $cursor = $slotEndTs;
        }

        return $slots;
    }

    /**
     * Books a slot if it's still open. Re-validates server-side even though
     * the UI only ever shows open slots, and relies on the DB unique
     * constraint as the final race-condition-safe guard.
     */
    public function book(int $trainerId, int $memberId, string $date, string $startTime): array
    {
        $openSlots = $this->openSlotsFor($trainerId, $date);
        $match = null;
        foreach ($openSlots as $slot) {
            if ($slot['start'] === $startTime) {
                $match = $slot;
                break;
            }
        }

        if (!$match) {
            return ['success' => false, 'message' => 'That slot is no longer available. Please choose another.'];
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO trainer_booking (trainer_id, member_id, booking_date, start_time, end_time, status, created_at)
                 VALUES (:trainer_id, :member_id, :date, :start, :end, "confirmed", NOW())'
            );
            $stmt->execute([
                'trainer_id' => $trainerId,
                'member_id' => $memberId,
                'date' => $date,
                'start' => $match['start'],
                'end' => $match['end'],
            ]);
            return ['success' => true, 'message' => 'Session booked for ' . date('d M Y', strtotime($date)) . ', ' . $match['label'] . '.'];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['success' => false, 'message' => 'That slot was just booked by someone else. Please choose another.'];
            }
            throw $e;
        }
    }

    public function upcomingForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, t.name AS trainer_name, t.slug AS trainer_slug
             FROM trainer_booking b JOIN trainers t ON t.id = b.trainer_id
             WHERE b.member_id = :member_id AND b.status = 'confirmed' AND b.booking_date >= CURDATE()
             ORDER BY b.booking_date ASC, b.start_time ASC"
        );
        $stmt->execute(['member_id' => $memberId]);
        return $stmt->fetchAll();
    }

    /** All upcoming confirmed bookings for a trainer, with the member's name — used by the admin trainer detail page. */
    public function upcomingForTrainer(int $trainerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, u.name AS member_name
             FROM trainer_booking b
             JOIN members m ON m.id = b.member_id
             JOIN users u ON u.id = m.user_id
             WHERE b.trainer_id = :trainer_id AND b.status = 'confirmed' AND b.booking_date >= CURDATE()
             ORDER BY b.booking_date ASC, b.start_time ASC"
        );
        $stmt->execute(['trainer_id' => $trainerId]);
        return $stmt->fetchAll();
    }

    /** Count of all upcoming confirmed bookings across all trainers — used by the admin dashboard. */
    public function upcomingCount(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM trainer_booking WHERE status = 'confirmed' AND booking_date >= CURDATE()"
        )->fetchColumn();
    }
}
