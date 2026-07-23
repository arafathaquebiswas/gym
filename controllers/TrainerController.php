<?php

final class TrainerController extends Controller
{
    private const MAX_BOOKING_DAYS_AHEAD = 14;

    public function show(string $slug): void
    {
        if (!Feature::trainerModuleOn()) {
            $this->abort404();
        }

        $trainerModel = new Trainer();
        $trainer = $trainerModel->findBySlug($slug);

        if (!$trainer) {
            $this->abort404();
        }

        $scheduleModel = new TrainerSchedule();
        $reviewModel = new TrainerReview();

        $canReview = false;
        if (Auth::hasRole('member')) {
            $member = (new Member())->findByUserId((int) Auth::user()['id']);
            $canReview = $member && $reviewModel->canReview((int) $trainer['id'], (int) $member['id']);
        }

        $this->view('trainer-detail', [
            'trainer' => $trainer,
            'weeklySchedule' => $scheduleModel->weeklyFor((int) $trainer['id']),
            'dailyHours' => $scheduleModel->typicalDailyHours((int) $trainer['id']),
            'maxBookingDate' => date('Y-m-d', strtotime('+' . self::MAX_BOOKING_DAYS_AHEAD . ' days')),
            'minBookingDate' => date('Y-m-d'),
            'gallery' => (new TrainerGallery())->forTrainer((int) $trainer['id']),
            'reviews' => $reviewModel->forTrainer((int) $trainer['id']),
            'averageRating' => $reviewModel->averageRating((int) $trainer['id']),
            'reviewCount' => $reviewModel->count((int) $trainer['id']),
            'canReview' => $canReview,
        ]);
    }

    public function submitReview(string $slug): void
    {
        Auth::requireRole('member');
        Security::requireCsrf();

        if (!Feature::trainerModuleOn()) {
            $this->abort404();
        }

        $trainerModel = new Trainer();
        $trainer = $trainerModel->findBySlug($slug);
        if (!$trainer) {
            $this->abort404();
        }

        $member = (new Member())->findByUserId((int) Auth::user()['id']);
        $reviewModel = new TrainerReview();

        if (!$member || !$reviewModel->canReview((int) $trainer['id'], (int) $member['id'])) {
            flash('danger', 'You can only review a trainer you have booked a session with, once.');
            redirect('trainers/' . $slug);
        }

        $rating = (int) $this->input('rating');
        if ($rating < 1 || $rating > 5) {
            flash('danger', 'Please choose a rating between 1 and 5.');
            redirect('trainers/' . $slug);
        }

        $comment = trim($this->rawInput('comment'));
        $reviewModel->submit((int) $trainer['id'], (int) $member['id'], $rating, $comment !== '' ? $comment : null);

        flash('success', 'Thanks for your review!');
        redirect('trainers/' . $slug);
    }

    public function book(string $slug): void
    {
        Auth::requireRole('member');
        Security::requireCsrf();

        if (!Feature::trainerBookingOn()) {
            $this->abort404();
        }

        $trainerModel = new Trainer();
        $trainer = $trainerModel->findBySlug($slug);
        if (!$trainer) {
            $this->abort404();
        }

        $date = $this->input('date');
        $startTime = $this->input('start_time');

        $minDate = date('Y-m-d');
        $maxDate = date('Y-m-d', strtotime('+' . self::MAX_BOOKING_DAYS_AHEAD . ' days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < $minDate || $date > $maxDate) {
            flash('danger', 'Please choose a valid date within the next two weeks.');
            redirect('trainers/' . $slug);
        }
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $startTime)) {
            flash('danger', 'Please choose a valid time slot.');
            redirect('trainers/' . $slug);
        }

        $memberModel = new Member();
        $member = $memberModel->findByUserId((int) Auth::user()['id']);
        if (!$member) {
            flash('danger', 'We could not find your member profile.');
            redirect('trainers/' . $slug);
        }

        $bookingModel = new TrainerBooking();
        $result = $bookingModel->book((int) $trainer['id'], (int) $member['id'], $date, $startTime);

        flash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect('trainers/' . $slug);
    }
}
