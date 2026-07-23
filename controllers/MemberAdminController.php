<?php

final class MemberAdminController extends AdminController
{
    private const ALLOWED_PAYMENT_METHODS = ['cash', 'card', 'bkash', 'nagad', 'rocket', 'bank_transfer'];
    private const NO_REFERENCE_METHODS = ['cash', 'card'];

    /** Validates a mandatory payment method + (when required) a transaction/reference ID. Flashes + redirects on failure. */
    private function requirePaymentMethod(string $redirectTo): array
    {
        $method = $this->input('payment_method', '');
        if (!in_array($method, self::ALLOWED_PAYMENT_METHODS, true)) {
            flash('danger', 'Please select a payment method.');
            redirect($redirectTo);
        }

        $referenceNo = $this->input('reference_no') ?: null;
        if (!in_array($method, self::NO_REFERENCE_METHODS, true) && !$referenceNo) {
            flash('danger', 'Please enter the transaction/reference ID for the selected payment method.');
            redirect($redirectTo);
        }

        return [$method, $referenceNo];
    }

    /**
     * The stricter membership-specific gate (Add Member + Renew Membership): every method
     * requires an Amount Received, and each non-cash method has its own mandatory proof-of-
     * payment fields (bKash/Nagad/Rocket number + TrxID, Card approval number, Bank name +
     * reference). A member can never become Active without these — that's the whole point.
     * @return array{0:string,1:?string,2:array<string,?string>,3:float}
     */
    private function requireMembershipPayment(string $redirectTo): array
    {
        $method = $this->input('payment_method', '');
        if (!in_array($method, self::ALLOWED_PAYMENT_METHODS, true)) {
            flash('danger', 'Please select a payment method. The membership cannot be activated without one.');
            redirect($redirectTo);
        }

        $amountInput = $this->input('price_paid', '');
        if ($amountInput === '' || (float) $amountInput <= 0) {
            flash('danger', 'Please enter the Amount Received.');
            redirect($redirectTo);
        }

        $referenceNo = $this->input('reference_no') ?: null;
        $details = [
            'payer_number' => $this->input('payer_number') ?: null,
            'card_type' => $this->input('card_type') ?: null,
            'card_last4' => $this->input('card_last4') ?: null,
            'bank_name' => $this->input('bank_name') ?: null,
            'account_number' => $this->input('account_number') ?: null,
        ];

        $missing = [];
        switch ($method) {
            case 'bkash':
                if (!$details['payer_number']) $missing[] = 'bKash Number';
                if (!$referenceNo) $missing[] = 'Transaction ID';
                break;
            case 'nagad':
                if (!$details['payer_number']) $missing[] = 'Nagad Number';
                if (!$referenceNo) $missing[] = 'Transaction ID';
                break;
            case 'rocket':
                if (!$details['payer_number']) $missing[] = 'Rocket Number';
                if (!$referenceNo) $missing[] = 'Transaction ID';
                break;
            case 'card':
                if (!$referenceNo) $missing[] = 'Transaction / Approval Number';
                break;
            case 'bank_transfer':
                if (!$details['bank_name']) $missing[] = 'Bank Name';
                if (!$referenceNo) $missing[] = 'Reference Number';
                break;
        }

        if ($missing) {
            flash('danger', 'Cannot activate the membership — missing: ' . implode(', ', $missing) . '.');
            redirect($redirectTo);
        }

        return [$method, $referenceNo, $details, (float) $amountInput];
    }

    public function index(): void
    {
        $memberModel = new Member();
        $memberModel->syncAllStatuses();

        $filters = [
            'search' => $this->input('search'),
            'status' => $this->input('status'),
            'trainer_id' => $this->input('trainer_id'),
            'sort' => $this->input('sort'),
        ];
        $page = max(1, (int) $this->input('page', '1'));

        $result = $memberModel->paginateForAdmin($filters, $page);

        $this->adminView('members/index', [
            'pageTitle' => 'Members',
            'members' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
            'trainers' => (new Trainer())->allActive(),
            'packages' => (new Package())->allForAdmin(),
            'stats' => $memberModel->adminStatistics(),
        ]);
    }

    public function create(): void
    {
        $this->adminView('members/form', [
            'pageTitle' => 'Add Member',
            'member' => null,
            'trainers' => (new Trainer())->allActive(),
            'packages' => (new Package())->allForAdmin(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        $email = $this->input('email') ?: null;
        $phone = $this->input('phone');
        $password = $this->rawInput('password');

        $validator = new Validator(['name' => $name, 'phone' => $phone, 'email' => $email]);
        $validator->required('name', 'Name')->required('phone', 'Phone Number')->phone('phone')->email('email');

        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('admin/members/create');
        }

        $userModel = new User();
        if ($email && $userModel->emailExists($email)) {
            flash('danger', 'An account with this email already exists.');
            redirect('admin/members/create');
        }

        // A walk-in "Add Member" is always a live sale at the desk — package + a fully
        // verified payment are mandatory here (unlike public self-registration, which still
        // creates an unpaid Pending member with no package, per the online-registration flow).
        $packageId = (int) $this->input('package_id');
        $package = $packageId > 0 ? (new Package())->find($packageId) : null;
        if (!$package) {
            flash('danger', 'Please select a membership package.');
            redirect('admin/members/create');
        }
        [$paymentMethod, $referenceNo, $paymentDetails, $amountReceived] = $this->requireMembershipPayment('admin/members/create');

        if (!$email) {
            $email = $this->generatePlaceholderEmail($phone);
        }
        if ($password === '') {
            $password = bin2hex(random_bytes(5));
        }

        $userId = $userModel->create($name, $email, $phone, $password, 'member');

        $memberModel = new Member();
        $data = $this->collectMemberData();

        $photoPath = Upload::handle($_FILES['photo'] ?? [], 'members');
        if ($photoPath) {
            $data['photo'] = $photoPath;
        }

        $memberId = $memberModel->createForNewUser($userId, $data);

        $startDate = $this->input('start_date') ?: date('Y-m-d');
        $this->createInitialSubscription($memberId, $package, $startDate, $paymentMethod, $referenceNo, $paymentDetails, $amountReceived);
        $memberModel->recomputeStatus($memberId);

        $this->logActivity('member_created', "Created member #$memberId: $name");
        flash('success', 'Member added successfully.');
        redirect('admin/members/' . $memberId);
    }

    public function edit(string $id): void
    {
        $memberModel = new Member();
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $this->adminView('members/form', [
            'pageTitle' => 'Edit Member',
            'member' => $member,
            'trainers' => (new Trainer())->allActive(),
            'packages' => (new Package())->allForAdmin(),
        ]);
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $name = $this->input('name');
        $email = $this->input('email');
        $phone = $this->input('phone');

        $validator = new Validator(['name' => $name, 'email' => $email, 'phone' => $phone]);
        $validator->required('name', 'Name')->required('email', 'Email')->email('email')->phone('phone');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('admin/members/' . $id . '/edit');
        }

        $userModel = new User();
        $userModel->update((int) $member['user_id'], ['name' => $name, 'email' => $email, 'phone' => $phone]);

        $data = $this->collectMemberData();

        $photoPath = Upload::handle($_FILES['photo'] ?? [], 'members');
        if ($photoPath) {
            Upload::delete($member['photo']);
            $data['photo'] = $photoPath;
        }

        $memberModel->update((int) $id, $data);

        $this->logActivity('member_updated', "Updated member #$id: $name");
        flash('success', 'Member updated successfully.');
        redirect('admin/members/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        Upload::delete($member['photo']);
        (new User())->delete((int) $member['user_id']);

        $this->logActivity('member_deleted', "Deleted member #$id: {$member['name']}");
        flash('success', 'Member deleted.');
        redirect('admin/members');
    }

    public function show(string $id): void
    {
        $memberModel = new Member();
        $memberModel->recomputeStatus((int) $id);
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $attendanceModel = new Attendance();
        $settingModel = new Setting();

        $trainerFeeDefault = $settingModel->getFloat('default_trainer_fee');
        if (!empty($member['trainer_id'])) {
            $trainer = (new Trainer())->find((int) $member['trainer_id']);
            if ($trainer) {
                $trainerFeeDefault = (float) $trainer['display_price'];
            }
        }

        $this->adminView('members/show', [
            'pageTitle' => $member['name'],
            'member' => $member,
            'subscriptionHistory' => (new MemberSubscription())->history((int) $id),
            'attendanceLog' => $attendanceModel->recentForMember((int) $id),
            'openSession' => $attendanceModel->openSessionForMember((int) $id),
            'packages' => (new Package())->allForAdmin(),
            'trainerFeeDefault' => $trainerFeeDefault,
            'lockerFineDefault' => $settingModel->getFloat('lost_locker_fine'),
        ]);
    }

    public function paymentHistory(string $id): void
    {
        $memberModel = new Member();
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $this->adminView('members/payment-history', [
            'pageTitle' => 'Payment History — ' . $member['name'],
            'member' => $member,
            'payments' => (new Payment())->forMember((int) $id),
        ]);
    }

    public function renew(string $id): void
    {
        Security::requireCsrf();

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $packageId = (int) $this->input('package_id');
        $package = (new Package())->find($packageId);
        if (!$package) {
            flash('danger', 'Please select a valid package.');
            redirect('admin/members/' . $id);
        }

        [$paymentMethod, $referenceNo, $paymentDetails, $pricePaid] = $this->requireMembershipPayment('admin/members/' . $id);

        $startDate = $this->input('start_date') ?: date('Y-m-d');
        $couponCode = $this->input('coupon_code') ?: null;
        $durationDays = $this->input('duration_days') !== '' ? (int) $this->input('duration_days') : null;
        $discountAmount = $this->input('discount') !== '' ? (float) $this->input('discount') : null;
        $notes = $this->input('notes') ?: null;
        $trainerId = $this->input('trainer_id');

        $result = $this->renewMember((int) $id, $package, $startDate, $pricePaid, $paymentMethod, $couponCode, $referenceNo, $durationDays, $discountAmount, $notes, $paymentDetails);
        if ($result === false) {
            flash('danger', 'That coupon code is invalid, expired, or no longer applicable.');
            redirect('admin/members/' . $id);
        }

        if (Feature::trainerModuleOn() && $trainerId !== null && $trainerId !== '') {
            $memberModel->update((int) $id, ['trainer_id' => (int) $trainerId]);
        }

        $this->logActivity('member_renewed', "Renewed membership for member #$id ({$package['name']})");
        flash('success', 'Membership renewed successfully.');
        redirect('admin/members/' . $id);
    }

    public function bulkAction(): void
    {
        Security::requireCsrf();

        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $action = $this->input('bulk_action');

        if (!$ids) {
            flash('danger', 'No members selected.');
            redirect('admin/members');
        }

        $memberModel = new Member();
        $count = 0;

        switch ($action) {
            case 'delete':
                $userModel = new User();
                foreach ($ids as $id) {
                    $member = $memberModel->find($id);
                    if (!$member) {
                        continue;
                    }
                    Upload::delete($member['photo']);
                    $userModel->delete((int) $member['user_id']);
                    $count++;
                }
                $this->logActivity('members_bulk_deleted', "Bulk-deleted $count member(s)");
                flash('success', "$count member(s) deleted.");
                break;

            case 'renew':
                $packageId = (int) $this->input('package_id');
                $package = (new Package())->find($packageId);
                if (!$package) {
                    flash('danger', 'Please select a valid package.');
                    redirect('admin/members');
                }
                [$paymentMethod, $referenceNo] = $this->requirePaymentMethod('admin/members');
                $startDate = $this->input('start_date') ?: date('Y-m-d');
                $couponCode = $this->input('coupon_code') ?: null;
                foreach ($ids as $id) {
                    if (!$memberModel->find($id)) {
                        continue;
                    }
                    $pricePaid = (float) $package['regular_price'];
                    if ($this->renewMember($id, $package, $startDate, $pricePaid, $paymentMethod, $couponCode, $referenceNo) !== false) {
                        $count++;
                    }
                }
                $this->logActivity('members_bulk_renewed', "Bulk-renewed $count member(s) ({$package['name']})");
                flash('success', "$count member(s) renewed.");
                break;

            case 'assign_trainer':
                if (!Feature::trainerModuleOn()) {
                    flash('danger', 'The trainer module is currently disabled.');
                    redirect('admin/members');
                }
                $trainerId = (int) $this->input('trainer_id');
                if (!$trainerId || !(new Trainer())->find($trainerId)) {
                    flash('danger', 'Please select a valid trainer.');
                    redirect('admin/members');
                }
                foreach ($ids as $id) {
                    if ($memberModel->find($id)) {
                        $memberModel->update($id, ['trainer_id' => $trainerId]);
                        $count++;
                    }
                }
                $this->logActivity('members_bulk_trainer_assigned', "Bulk-assigned trainer #$trainerId to $count member(s)");
                flash('success', "Trainer assigned to $count member(s).");
                break;

            case 'assign_locker':
                $lockers = (array) ($_POST['locker'] ?? []);
                foreach ($ids as $id) {
                    $locker = trim((string) ($lockers[$id] ?? ''));
                    if ($locker === '' || !$memberModel->find($id)) {
                        continue;
                    }
                    $memberModel->update($id, ['locker_number' => $locker]);
                    $count++;
                }
                $this->logActivity('members_bulk_locker_assigned', "Bulk-assigned lockers to $count member(s)");
                flash('success', "Locker assigned to $count member(s).");
                break;

            case 'notify':
                $subject = $this->input('notify_subject');
                $message = $this->rawInput('notify_message');
                if ($subject === '' || $message === '') {
                    flash('danger', 'Please provide both a subject and a message.');
                    redirect('admin/members');
                }
                foreach ($ids as $id) {
                    $member = $memberModel->find($id);
                    if (!$member || empty($member['notify_email'])) {
                        continue;
                    }
                    Mailer::send($member['email'], $member['name'], $subject, nl2br(e($message)));
                    $count++;
                }
                $this->logActivity('members_bulk_notified', "Bulk-notified $count member(s): $subject");
                flash('success', "Notification sent to $count member(s).");
                break;

            default:
                flash('danger', 'Invalid bulk action.');
        }

        redirect('admin/members');
    }

    public function checkIn(string $id): void
    {
        Security::requireCsrf();

        $memberModel = new Member();
        if (!$memberModel->find((int) $id)) {
            $this->abort404();
        }

        $attendanceModel = new Attendance();
        if ($attendanceModel->openSessionForMember((int) $id)) {
            flash('danger', 'This member already has an open check-in.');
            redirect('admin/members/' . $id);
        }

        $attendanceModel->checkIn((int) $id, (int) Auth::user()['id']);
        $this->logActivity('member_checked_in', "Checked in member #$id");
        flash('success', 'Member checked in.');
        redirect('admin/members/' . $id);
    }

    public function checkOut(string $id): void
    {
        Security::requireCsrf();

        $attendanceModel = new Attendance();
        $session = $attendanceModel->openSessionForMember((int) $id);
        if (!$session) {
            flash('danger', 'No open check-in found for this member.');
            redirect('admin/members/' . $id);
        }

        $attendanceModel->checkOut((int) $session['id']);
        $this->logActivity('member_checked_out', "Checked out member #$id");
        flash('success', 'Member checked out.');
        redirect('admin/members/' . $id);
    }

    public function chargeTrainerFee(string $id): void
    {
        Security::requireCsrf();

        if (!Feature::trainerFeeOn()) {
            $this->abort404();
        }

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $amount = (float) $this->input('amount', '0');
        if ($amount <= 0) {
            flash('danger', 'Amount must be greater than zero.');
            redirect('admin/members/' . $id);
        }

        $settingModel = new Setting();
        if ($settingModel->getBool('tax_applies_to_trainer_fee', false)) {
            $amount = round($amount * (1 + $settingModel->getFloat('tax_percent') / 100), 2);
        }

        [$paymentMethod, $referenceNo] = $this->requirePaymentMethod('admin/members/' . $id);

        $trainerId = !empty($member['trainer_id']) ? (int) $member['trainer_id'] : null;

        (new Payment())->record([
            'member_id' => (int) $id,
            'trainer_id' => $trainerId,
            'type' => 'trainer_fee',
            'amount' => $amount,
            'method' => $paymentMethod,
            'reference_no' => $referenceNo,
            'recorded_by' => (int) Auth::user()['id'],
        ]);

        $this->logActivity('trainer_fee_charged', "Charged trainer fee of {$amount} to member #$id");
        flash('success', 'Trainer fee recorded successfully.');
        redirect('admin/members/' . $id);
    }

    public function chargeLockerFine(string $id): void
    {
        Security::requireCsrf();

        $memberModel = new Member();
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $amount = (float) $this->input('amount', '0');
        if ($amount <= 0) {
            flash('danger', 'Amount must be greater than zero.');
            redirect('admin/members/' . $id);
        }

        [$paymentMethod, $referenceNo] = $this->requirePaymentMethod('admin/members/' . $id);

        (new Payment())->record([
            'member_id' => (int) $id,
            'type' => 'locker_fine',
            'amount' => $amount,
            'method' => $paymentMethod,
            'reference_no' => $referenceNo,
            'recorded_by' => (int) Auth::user()['id'],
        ]);

        $this->logActivity('locker_fine_charged', "Charged locker fine of {$amount} to member #$id");
        flash('success', 'Locker fine recorded successfully.');
        redirect('admin/members/' . $id);
    }

    /** @param array<string,?string> $paymentDetails */
    private function createInitialSubscription(int $memberId, array $package, string $startDate, string $paymentMethod, ?string $referenceNo, array $paymentDetails, float $pricePaid): void
    {
        $endDate = (new DateTimeImmutable($startDate))
            ->modify('+' . (int) $package['duration_days'] . ' days')
            ->format('Y-m-d');

        $promotion = $this->applyMembershipCoupon($this->input('coupon_code') ?: null, $memberId, $pricePaid);
        if ($promotion) {
            $pricePaid = max(0, round($pricePaid - $promotion['discount'], 2));
        }

        $subscriptionModel = new MemberSubscription();
        $subscriptionId = $subscriptionModel->create([
            'member_id' => $memberId,
            'package_id' => (int) $package['id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price_paid' => $pricePaid,
            'created_by' => (int) Auth::user()['id'],
        ]);

        (new Payment())->record([
            'member_id' => $memberId,
            'subscription_id' => $subscriptionId,
            'type' => 'membership',
            'amount' => $pricePaid,
            'method' => $paymentMethod,
            'reference_no' => $referenceNo,
            'payer_number' => $paymentDetails['payer_number'] ?? null,
            'card_type' => $paymentDetails['card_type'] ?? null,
            'card_last4' => $paymentDetails['card_last4'] ?? null,
            'bank_name' => $paymentDetails['bank_name'] ?? null,
            'account_number' => $paymentDetails['account_number'] ?? null,
            'recorded_by' => (int) Auth::user()['id'],
        ]);

        if ($promotion) {
            (new Promotion())->recordUsage((int) $promotion['promo']['id'], $memberId, null, $subscriptionId);
        }

        (new Member())->ensureMoneyReceivedNo($memberId);
    }

    /** @return array{promo:array,discount:float}|null */
    private function applyMembershipCoupon(?string $couponCode, int $memberId, float $price): ?array
    {
        if (!$couponCode) {
            return null;
        }

        $promotionModel = new Promotion();
        $promo = $promotionModel->validCoupon($couponCode, $price, 'membership', $memberId);
        if (!$promo) {
            return null;
        }

        return ['promo' => $promo, 'discount' => $promotionModel->computeDiscount($promo, $price)];
    }

    /** Creates a renewal subscription + payment record for one member. Returns false only when an explicitly-supplied coupon code was invalid. */
    /** @param array<string,?string> $paymentDetails */
    private function renewMember(int $memberId, array $package, string $startDate, float $pricePaid, string $paymentMethod, ?string $couponCode, ?string $referenceNo = null, ?int $durationDaysOverride = null, ?float $discountAmount = null, ?string $notes = null, array $paymentDetails = []): bool
    {
        $promotion = $this->applyMembershipCoupon($couponCode, $memberId, $pricePaid);
        if ($couponCode && !$promotion) {
            return false;
        }
        if ($promotion) {
            $pricePaid = max(0, round($pricePaid - $promotion['discount'], 2));
        }
        if ($discountAmount) {
            $pricePaid = max(0, round($pricePaid - $discountAmount, 2));
        }

        $settingModel = new Setting();
        if ($settingModel->getBool('tax_applies_to_membership', false)) {
            $pricePaid = round($pricePaid * (1 + $settingModel->getFloat('tax_percent') / 100), 2);
        }

        $durationDays = $durationDaysOverride ?? (int) $package['duration_days'];
        $endDate = (new DateTimeImmutable($startDate))
            ->modify("+$durationDays days")
            ->format('Y-m-d');

        $subscriptionModel = new MemberSubscription();
        $subscriptionId = $subscriptionModel->create([
            'member_id' => $memberId,
            'package_id' => (int) $package['id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price_paid' => $pricePaid,
            'discount_amount' => $discountAmount,
            'notes' => $notes,
            'created_by' => (int) Auth::user()['id'],
        ]);

        (new Payment())->record([
            'member_id' => $memberId,
            'subscription_id' => $subscriptionId,
            'type' => 'membership',
            'amount' => $pricePaid,
            'method' => $paymentMethod,
            'reference_no' => $referenceNo,
            'payer_number' => $paymentDetails['payer_number'] ?? null,
            'card_type' => $paymentDetails['card_type'] ?? null,
            'card_last4' => $paymentDetails['card_last4'] ?? null,
            'bank_name' => $paymentDetails['bank_name'] ?? null,
            'account_number' => $paymentDetails['account_number'] ?? null,
            'recorded_by' => (int) Auth::user()['id'],
        ]);

        if ($promotion) {
            (new Promotion())->recordUsage((int) $promotion['promo']['id'], $memberId, null, $subscriptionId);
        }

        $memberModel = new Member();
        $memberModel->ensureMoneyReceivedNo($memberId);
        $memberModel->recomputeStatus($memberId);

        return true;
    }

    /**
     * Walk-in members registered by the admin often have no email — but `users.email` is a
     * required unique login field, and there's no member-facing login in this app yet (that's
     * expected — see the online-registration flow), so a stable, collision-free placeholder is
     * enough to satisfy the schema without pretending it's a real contact address.
     */
    private function generatePlaceholderEmail(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?: (string) time();
        $userModel = new User();
        $email = "member{$digits}@no-email.powersurgegym.local";
        $suffix = 1;
        while ($userModel->emailExists($email)) {
            $suffix++;
            $email = "member{$digits}-{$suffix}@no-email.powersurgegym.local";
        }
        return $email;
    }

    private function collectMemberData(): array
    {
        $data = [
            'dob' => $this->input('dob') ?: null,
            'gender' => $this->input('gender') ?: null,
            'blood_group' => $this->input('blood_group') ?: null,
            'emergency_contact' => $this->input('emergency_contact') ?: null,
            'address' => $this->input('address') ?: null,
            'height_cm' => $this->input('height_cm') !== '' ? (float) $this->input('height_cm') : null,
            'weight_kg' => $this->input('weight_kg') !== '' ? (float) $this->input('weight_kg') : null,
            'fitness_goal' => $this->input('fitness_goal') ?: null,
            'medical_notes' => $this->rawInput('medical_notes') ?: null,
            'join_date' => $this->input('join_date') ?: date('Y-m-d'),
            'locker_number' => $this->input('locker_number') ?: null,
        ];

        // Only touch trainer_id when the trainer module is enabled — the field isn't
        // rendered in the form when disabled, so never null out an existing assignment.
        if (Feature::trainerModuleOn()) {
            $data['trainer_id'] = $this->input('trainer_id') !== '' ? (int) $this->input('trainer_id') : null;
        }

        return $data;
    }
}
