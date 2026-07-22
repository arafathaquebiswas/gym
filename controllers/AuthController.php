<?php

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirectToDashboard();
        }
        $this->view('login');
    }

    public function login(): void
    {
        Security::requireCsrf();

        $email = $this->input('email');
        $password = $this->rawInput('password');
        $db = Database::connection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (Auth::isLockedOut($db, $email, $ip)) {
            flash('danger', 'Too many failed attempts. Please try again in 15 minutes.');
            redirect('login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Auth::logAttempt($db, $user['id'] ?? null, $email, 'failed');
            flash('danger', 'Invalid email or password.');
            redirect('login');
        }

        if ($user['status'] !== 'active') {
            flash('danger', 'Your account is not active. Please contact the gym.');
            redirect('login');
        }

        Auth::logAttempt($db, (int) $user['id'], $email, 'success');
        Auth::login($user);
        $userModel->touchLastLogin((int) $user['id']);

        flash('success', 'Welcome back, ' . $user['name'] . '!');
        $this->redirectToDashboard();
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirectToDashboard();
        }
        $this->view('register');
    }

    public function register(): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        $email = $this->input('email');
        $phone = $this->input('phone');
        $password = $this->rawInput('password');
        $passwordConfirm = $this->rawInput('password_confirm');

        $validator = new Validator([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'password_confirm' => $passwordConfirm,
        ]);
        $validator->required('name', 'Full name')
            ->required('email', 'Email')
            ->email('email')
            ->phone('phone')
            ->minLength('password', 8, 'Password')
            ->matches('password_confirm', 'password', 'Password confirmation');

        $userModel = new User();
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            $_SESSION['_old'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
            redirect('register');
        }

        if ($userModel->emailExists($email)) {
            flash('danger', 'An account with this email already exists.');
            $_SESSION['_old'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
            redirect('register');
        }

        $userId = $userModel->create($name, $email, $phone, $password, 'member');
        $memberModel = new Member();
        $memberModel->createForUser($userId);

        $user = $userModel->findById($userId);
        Auth::login($user);

        Mailer::send($email, $name, 'Welcome to PowerSurge Gym', "<p>Hi {$name},</p><p>Your account has been created. Visit the gym to activate a membership package.</p>");

        flash('success', 'Account created! Visit the front desk to activate your membership.');
        redirect('account');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been logged out.');
        redirect('login');
    }

    public function account(): void
    {
        Auth::requireRole('member');
        $memberModel = new Member();
        $member = $memberModel->findByUserId((int) Auth::user()['id']);
        $subscription = $member ? $memberModel->activeSubscription((int) $member['id']) : null;
        $bookings = $member ? (new TrainerBooking())->upcomingForMember((int) $member['id']) : [];

        $this->view('account', ['member' => $member, 'subscription' => $subscription, 'bookings' => $bookings]);
    }

    private function redirectToDashboard(): never
    {
        redirect(Auth::isStaff() ? 'admin' : 'account');
    }
}
