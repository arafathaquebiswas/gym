<?php

final class TrainerSchedule extends Model
{
    public const DAY_LABELS = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    /** @return array<int, array> 7 rows indexed by day_of_week (0-6) */
    public function weeklyFor(int $trainerId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM trainer_schedule WHERE trainer_id = :trainer_id ORDER BY day_of_week ASC');
        $stmt->execute(['trainer_id' => $trainerId]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int) $row['day_of_week']] = $row;
        }
        return $rows;
    }

    public function forDay(int $trainerId, int $dayOfWeek): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM trainer_schedule WHERE trainer_id = :trainer_id AND day_of_week = :day LIMIT 1');
        $stmt->execute(['trainer_id' => $trainerId, 'day' => $dayOfWeek]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Replaces the trainer's weekly schedule from admin form input.
     * @param array<int, array{is_off?:string, start?:string, end?:string}> $days keyed 0-6
     */
    public function saveWeek(int $trainerId, array $days): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO trainer_schedule (trainer_id, day_of_week, start_time, end_time, is_off)
             VALUES (:trainer_id, :day, :start, :end, :is_off)
             ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time), is_off = VALUES(is_off)'
        );

        foreach (range(0, 6) as $day) {
            $row = $days[$day] ?? [];
            $isOff = !empty($row['is_off']);
            $start = ($isOff || empty($row['start'])) ? null : $row['start'];
            $end = ($isOff || empty($row['end'])) ? null : $row['end'];

            $stmt->execute([
                'trainer_id' => $trainerId,
                'day' => $day,
                'start' => $start,
                'end' => $end,
                'is_off' => $isOff ? 1 : 0,
            ]);
        }
    }

    /**
     * Typical working hours per day, used for the "X Hours/Day" card stat —
     * takes the first non-off day's shift length as the representative value.
     */
    public function typicalDailyHours(int $trainerId): ?int
    {
        foreach ($this->weeklyFor($trainerId) as $day) {
            if (!$day['is_off'] && $day['start_time'] && $day['end_time']) {
                $start = strtotime($day['start_time']);
                $end = strtotime($day['end_time']);
                return (int) round(($end - $start) / 3600);
            }
        }
        return null;
    }
}
