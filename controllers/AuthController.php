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

        // There is no member-facing login in this app — members are never given credentials
        // and can never sign in, even if a (purely internal, never-disclosed) account record
        // exists underneath their membership. Treat it exactly like a wrong password so it
        // reveals nothing about whether that email belongs to a member.
        if (!$user || $user['role_slug'] === 'member' || !password_verify($password, $user['password_hash'])) {
            Auth::logAttempt($db, $user['id'] ?? null, $email, 'failed');
            flash('danger', 'Invalid email or password.');
            redirect('login');
        }

        if ($user['status'] !== 'active') {
            flash('danger', 'Your account is not active. Please contact the gym.');
            redirect('login');
        }

        Auth::logAttempt($db, (int) $user['id'], $email, 'success');

        // Capture the guest session's cart token before Auth::login() regenerates the session ID.
        $guestCartToken = session_id();
        Auth::login($user);
        (new Cart())->mergeGuestIntoUser($guestCartToken, (int) $user['id']);

        $userModel->touchLastLogin((int) $user['id']);

        flash('success', 'Welcome back, ' . $user['name'] . '!');
        $this->redirectToDashboard();
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been logged out.');
        redirect('login');
    }

    /** Only staff (admin) and delivery roles can ever reach this — see the member-login block above. */
    private function redirectToDashboard(): never
    {
        redirect(Auth::hasRole('delivery') ? 'delivery' : 'admin');
    }
}
