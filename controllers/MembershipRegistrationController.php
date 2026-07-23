<?php

/**
 * Public "Online Membership Registration" — replaces member sign-up. This app has no
 * member-facing login: submitting this form never creates an account the visitor can use,
 * never logs anyone in, and never issues credentials. It only creates a Pending member row
 * for staff to find and activate in person (see MemberAdminController::renew()/store()).
 */
final class MembershipRegistrationController extends Controller
{
    public function show(): void
    {
        $this->view('membership-register', [
            'pageTitle' => 'Register for Membership',
            'packages' => (new Package())->allActive(),
            'trainers' => Feature::trainerModuleOn() ? (new Trainer())->allActive() : [],
        ]);
    }

    public function submit(): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        $phone = $this->input('phone');
        $email = $this->input('email') ?: null;

        $validator = new Validator(['name' => $name, 'phone' => $phone, 'email' => $email]);
        $validator->required('name', 'Full name')->required('phone', 'Phone number')->phone('phone')->email('email');

        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            $_SESSION['_old'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
            redirect('register');
        }

        $preferredPackageId = (int) $this->input('preferred_package_id');
        $preferredPackage = $preferredPackageId > 0 ? (new Package())->find($preferredPackageId) : null;
        if (!$preferredPackage) {
            flash('danger', 'Please select a preferred package.');
            $_SESSION['_old'] = ['name' => $name, 'email' => $email, 'phone' => $phone];
            redirect('register');
        }

        $userModel = new User();
        if ($email && $userModel->emailExists($email)) {
            flash('danger', 'A registration with this email already exists. Please contact the gym office if this is a mistake.');
            redirect('register');
        }
        if (!$email) {
            $email = $userModel->placeholderEmail($phone);
        }

        // No credentials are ever generated to be given out — this password is random and
        // discarded; it exists only because `users.password_hash` is NOT NULL underneath.
        $userId = $userModel->create($name, $email, $phone, bin2hex(random_bytes(16)), 'member');

        // Self-reported only — never auto-verified, never activates anything by itself. It just
        // gives staff a head start when the visitor says "I already paid online" at the office.
        $reportedMethod = $this->input('reported_payment_method');
        if (!in_array($reportedMethod, ['bkash', 'nagad', 'rocket', 'card', 'bank_transfer'], true)) {
            $reportedMethod = null;
        }

        $data = [
            'gender' => $this->input('gender') ?: null,
            'dob' => $this->input('dob') ?: null,
            'address' => $this->input('address') ?: null,
            'emergency_contact' => $this->input('emergency_contact') ?: null,
            'registration_notes' => $this->rawInput('notes') ?: null,
            'preferred_package_id' => (int) $preferredPackage['id'],
            'reported_payment_method' => $reportedMethod,
            'reported_payment_reference' => $this->input('reported_payment_reference') ?: null,
            'reported_payer_number' => $this->input('reported_payer_number') ?: null,
        ];
        if (Feature::trainerModuleOn()) {
            $trainerId = (int) $this->input('trainer_id');
            $data['trainer_id'] = $trainerId > 0 ? $trainerId : null;
        }

        $memberModel = new Member();
        $memberId = $memberModel->createForNewUser($userId, $data);

        if ((new Setting())->getBool('auto_email_notifications', true) && !str_ends_with($email, '@no-email.powersurgegym.local')) {
            Mailer::send(
                $email,
                $name,
                'Registration Received — PowerSurge Gym',
                "<p>Hi {$name},</p><p>We've received your membership registration request. Please visit or contact the PowerSurge Gym office to complete your payment and activate your membership.</p>"
            );
        }

        // Unauthenticated visitor — logged with no user_id, unlike every admin-attributed
        // logActivity() call elsewhere, since there's no staff member to attribute this to.
        Database::connection()->prepare(
            'INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
             VALUES (NULL, :action, :description, :ip, NOW())'
        )->execute([
            'action' => 'member_registered_online',
            'description' => "Online membership registration: #$memberId $name",
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

        flash('success', 'Your registration request has been received. Please visit or contact the PowerSurge Gym office to complete your payment and activate your membership.');
        redirect('register');
    }
}
