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

    /** Assigned to members.photo when no picture is uploaded — media_tile() resolves it under assets/images/. */
    private const DEFAULT_PROFILE_IMAGE = 'logo/logo.png';

    /** Payment screenshots get their own ceiling, above the 2MB used for profile pictures. */
    public const SCREENSHOT_MAX_BYTES = 5 * 1024 * 1024;

    private const PAYMENT_METHODS = ['bkash' => 'bKash', 'nagad' => 'Nagad'];
    private const PAYMENT_TYPES = ['qr' => 'QR Payment', 'mobile' => 'Mobile Number Payment'];

    public function submit(): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        $phone = $this->input('phone');
        $email = $this->input('email') ?: null;
        $address = $this->input('address');
        $emergencyContact = $this->input('emergency_contact');
        $currentWeight = $this->input('current_weight');
        $targetWeight = $this->input('target_weight');
        $couponCode = strtoupper($this->input('coupon_code'));
        $preferredPackageId = (int) $this->input('preferred_package_id');

        // Everything the visitor typed, so a rejected submit re-renders the form intact
        // instead of making them start over.
        $old = [
            'name' => $name, 'email' => $email, 'phone' => $phone,
            'address' => $address, 'emergency_contact' => $emergencyContact,
            'gender' => $this->input('gender'), 'dob' => $this->input('dob'),
            'current_weight' => $currentWeight, 'target_weight' => $targetWeight,
            'coupon_code' => $couponCode, 'notes' => $this->rawInput('notes'),
            'preferred_package_id' => (string) $preferredPackageId,
            'trainer_id' => $this->input('trainer_id'),
        ];

        $validator = new Validator([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'emergency_contact' => $emergencyContact,
        ]);
        $validator
            ->required('name', 'Full name')
            ->required('phone', 'Phone number')->phone('phone', 'Phone number')
            ->required('address', 'Address')
            ->required('emergency_contact', 'Emergency contact number')
            ->phone('emergency_contact', 'Emergency contact number')
            ->email('email');

        $errors = $validator->errors();

        $preferredPackage = $preferredPackageId > 0 ? (new Package())->find($preferredPackageId) : null;
        if (!$preferredPackage) {
            $errors['preferred_package_id'] = 'Please select a preferred package.';
        }

        foreach (['current_weight' => 'Current weight', 'target_weight' => 'Target weight'] as $field => $label) {
            $value = $old[$field];
            if ($value !== '' && (!is_numeric($value) || (float) $value <= 0 || (float) $value > 500)) {
                $errors[$field] = "$label must be a number between 1 and 500 kg.";
            }
        }

        // Optional: blank simply means no discount. A code that was typed, however, has to
        // resolve — silently ignoring a wrong one would let someone register believing a
        // discount applies. Checked against the package price so min_purchase is honoured.
        $coupon = null;
        $couponDiscount = 0.0;
        if ($couponCode !== '' && $preferredPackage) {
            $packagePrice = (float) $preferredPackage['display_price'];
            $promotionModel = new Promotion();
            $coupon = $promotionModel->validCoupon($couponCode, $packagePrice, 'membership');

            if (!$coupon) {
                $errors['coupon_code'] = 'Invalid coupon code.';
            } else {
                $couponDiscount = $promotionModel->computeDiscount($coupon, $packagePrice);
            }
        }

        $userModel = new User();
        if ($email && $userModel->emailExists($email)) {
            $errors['email'] = 'A registration with this email already exists. Please contact the gym office if this is a mistake.';
        }

        $photo = Upload::handle($_FILES['photo'] ?? [], 'members');
        if ($photo === null && Upload::lastError() !== null) {
            $errors['photo'] = Upload::lastError();
        }

        // ---- Online payment (bKash/Nagad) --------------------------------------
        // Only demanded when the visitor actually chose to pay online. "Pay at Gym"
        // leaves every payment column NULL, which is what keeps walk-ins out of the
        // admin verification queue.
        $payOnline = $this->input('payment_mode') === 'online';
        $old['payment_mode'] = $payOnline ? 'online' : 'gym';
        $paymentMethod = $this->input('payment_method');
        $paymentType = $this->input('payment_type');
        $transactionId = strtoupper($this->input('transaction_id'));
        $old['payment_method'] = $paymentMethod;
        $old['payment_type'] = $paymentType;
        $old['transaction_id'] = $transactionId;

        $screenshot = null;
        if ($payOnline) {
            if (!isset(self::PAYMENT_METHODS[$paymentMethod])) {
                $errors['payment_method'] = 'Please choose bKash or Nagad.';
            }
            if (!isset(self::PAYMENT_TYPES[$paymentType])) {
                $errors['payment_type'] = 'Please choose QR Payment or Mobile Number Payment.';
            }
            if ($transactionId === '') {
                $errors['transaction_id'] = 'Transaction ID is required.';
            } elseif (!preg_match('/^[A-Z0-9]{6,32}$/', $transactionId)) {
                $errors['transaction_id'] = 'Transaction ID should be 6-32 letters and numbers, e.g. 9A7BCD123EF.';
            } elseif ((new Member())->transactionIdExists($transactionId)) {
                // The unique index is the real guarantee; this only turns the
                // resulting constraint violation into something a visitor can act on.
                $errors['transaction_id'] = 'This Transaction ID has already been submitted. Please check the ID, or contact the gym office.';
            }

            $screenshot = Upload::handle($_FILES['payment_screenshot'] ?? [], 'payments', self::SCREENSHOT_MAX_BYTES);
            if ($screenshot === null) {
                $errors['payment_screenshot'] = Upload::lastError()
                    ?? 'Payment screenshot is required when paying online.';
            }
        }

        if ($errors !== []) {
            // Anything already written to disk is orphaned the moment we redirect —
            // the file input cannot be repopulated, so the visitor will re-pick it.
            Upload::delete($photo);
            Upload::delete($screenshot);

            $_SESSION['_old'] = $old;
            $_SESSION['_errors'] = $errors;
            flash('danger', count($errors) === 1
                ? reset($errors)
                : 'Please correct the highlighted fields and submit again.');
            redirect('register');
        }
        if (!$email) {
            $email = $userModel->placeholderEmail($phone);
        }

        // No credentials are ever generated to be given out — this password is random and
        // discarded; it exists only because `users.password_hash` is NOT NULL underneath.
        $userId = $userModel->create($name, $email, $phone, bin2hex(random_bytes(16)), 'member');

        $data = [
            'gender' => $this->input('gender') ?: null,
            'dob' => $this->input('dob') ?: null,
            'address' => $address,
            'emergency_contact' => $emergencyContact,
            // No picture uploaded is the normal case, not a gap to leave blank: the site
            // logo stands in so member listings never render an empty tile.
            'photo' => $photo ?? self::DEFAULT_PROFILE_IMAGE,
            'weight_kg' => $currentWeight !== '' ? (float) $currentWeight : null,
            'target_weight_kg' => $targetWeight !== '' ? (float) $targetWeight : null,
            'registration_coupon_code' => $coupon ? $coupon['code'] : null,
            'registration_coupon_discount' => $coupon ? $couponDiscount : null,
            'registration_amount' => (float) $preferredPackage['display_price'],
            'registration_notes' => $this->rawInput('notes') ?: null,
            'preferred_package_id' => (int) $preferredPackage['id'],
            // NULL across the board for "Pay at Gym" — nothing was paid online, so there
            // is nothing to verify and the row stays out of the verification queue.
            'payment_method' => $payOnline ? $paymentMethod : null,
            'payment_type' => $payOnline ? $paymentType : null,
            'transaction_id' => $payOnline ? $transactionId : null,
            'payment_screenshot' => $screenshot,
            'payment_status' => $payOnline ? 'pending' : null,
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
                'Registration Received — POWERSURGE GYM & NUTRITION',
                "<p>Hi {$name},</p><p>We've received your membership registration request. Please visit or contact the POWERSURGE GYM & NUTRITION office to complete your payment and activate your membership.</p>"
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

        $confirmation = $payOnline
            ? 'Your registration has been submitted successfully. Your payment is currently under verification. Your membership will be activated after the payment has been verified by our administrator.'
            : 'Your registration request has been received. Please visit or contact the POWERSURGE GYM & NUTRITION office to complete your payment and activate your membership.';
        if ($coupon) {
            $confirmation .= ' Coupon ' . $coupon['code'] . ' has been applied — ৳'
                . number_format($couponDiscount, 2) . ' off your '
                . $preferredPackage['name'] . ' package. Mention it at the office.';
        }

        flash('success', $confirmation);
        redirect('register');
    }
}
