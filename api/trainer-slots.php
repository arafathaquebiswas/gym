<?php
/**
 * AJAX endpoint: open booking slots for a trainer on a given date.
 * Read-only (GET), so no CSRF check — nothing is mutated here.
 */

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

$trainerId = (int) ($_GET['trainer_id'] ?? 0);
$date = (string) ($_GET['date'] ?? '');

$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+14 days'));

if ($trainerId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < $minDate || $date > $maxDate) {
    json_response(['success' => false, 'message' => 'Invalid trainer or date.', 'slots' => []], 422);
}

$bookingModel = new TrainerBooking();
$slots = $bookingModel->openSlotsFor($trainerId, $date);

json_response(['success' => true, 'slots' => $slots]);
