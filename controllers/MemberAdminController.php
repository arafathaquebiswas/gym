<?php

final class MemberAdminController extends AdminController
{
    public function index(): void
    {
        $memberModel = new Member();

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
        $email = $this->input('email');
        $phone = $this->input('phone');
        $password = $this->rawInput('password');

        $validator = new Validator(['name' => $name, 'email' => $email, 'phone' => $phone]);
        $validator->required('name', 'Name')->required('email', 'Email')->email('email')->phone('phone');

        $userModel = new User();
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('admin/members/create');
        }
        if ($userModel->emailExists($email)) {
            flash('danger', 'An account with this email already exists.');
            redirect('admin/members/create');
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

        $packageId = (int) $this->input('package_id');
        if ($packageId > 0) {
            $this->createInitialSubscription($memberId, $packageId);
        }

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
        $member = $memberModel->find((int) $id);
        if (!$member) {
            $this->abort404();
        }

        $attendanceModel = new Attendance();

        $this->adminView('members/show', [
            'pageTitle' => $member['name'],
            'member' => $member,
            'subscriptionHistory' => (new MemberSubscription())->history((int) $id),
            'attendanceLog' => $attendanceModel->recentForMember((int) $id),
            'openSession' => $attendanceModel->openSessionForMember((int) $id),
            'packages' => (new Package())->allForAdmin(),
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

        $startDate = $this->input('start_date') ?: date('Y-m-d');
        $pricePaid = (float) $this->input('price_paid', (string) $package['regular_price']);
        $paymentMethod = $this->input('payment_method', 'cash');
        $couponCode = $this->input('coupon_code') ?: null;

        $result = $this->renewMember((int) $id, $package, $startDate, $pricePaid, $paymentMethod, $couponCode);
        if ($result === false) {
            flash('danger', 'That coupon code is invalid, expired, or no longer applicable.');
            redirect('admin/members/' . $id);
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
                $startDate = $this->input('start_date') ?: date('Y-m-d');
                $paymentMethod = $this->input('payment_method', 'cash');
                $couponCode = $this->input('coupon_code') ?: null;
                foreach ($ids as $id) {
                    if (!$memberModel->find($id)) {
                        continue;
                    }
                    $pricePaid = (float) $package['regular_price'];
                    if ($this->renewMember($id, $package, $startDate, $pricePaid, $paymentMethod, $couponCode) !== false) {
                        $count++;
                    }
                }
                $this->logActivity('members_bulk_renewed', "Bulk-renewed $count member(s) ({$package['name']})");
                flash('success', "$count member(s) renewed.");
                break;

            case 'assign_trainer':
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

    private function createInitialSubscription(int $memberId, int $packageId): void
    {
        $package = (new Package())->find($packageId);
        if (!$package) {
            return;
        }

        $startDate = date('Y-m-d');
        $endDate = (new DateTimeImmutable($startDate))
            ->modify('+' . (int) $package['duration_days'] . ' days')
            ->format('Y-m-d');
        $pricePaid = (float) $this->input('price_paid', (string) $package['regular_price']);

        $promotion = $this->applyMembershipCoupon($this->input('coupon_code') ?: null, $memberId, $pricePaid);
        if ($promotion) {
            $pricePaid = max(0, round($pricePaid - $promotion['discount'], 2));
        }

        $subscriptionModel = new MemberSubscription();
        $subscriptionId = $subscriptionModel->create([
            'member_id' => $memberId,
            'package_id' => $packageId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price_paid' => $pricePaid,
            'created_by' => (int) Auth::user()['id'],
        ]);

        $stmt = Database::connection()->prepare(
            'INSERT INTO payments (member_id, subscription_id, type, amount, method, status, paid_at, recorded_by)
             VALUES (:member_id, :subscription_id, "membership", :amount, :method, "completed", NOW(), :recorded_by)'
        );
        $stmt->execute([
            'member_id' => $memberId,
            'subscription_id' => $subscriptionId,
            'amount' => $pricePaid,
            'method' => $this->input('payment_method', 'cash'),
            'recorded_by' => (int) Auth::user()['id'],
        ]);

        if ($promotion) {
            (new Promotion())->recordUsage((int) $promotion['promo']['id'], $memberId, null, $subscriptionId);
        }
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
    private function renewMember(int $memberId, array $package, string $startDate, float $pricePaid, string $paymentMethod, ?string $couponCode): bool
    {
        $promotion = $this->applyMembershipCoupon($couponCode, $memberId, $pricePaid);
        if ($couponCode && !$promotion) {
            return false;
        }
        if ($promotion) {
            $pricePaid = max(0, round($pricePaid - $promotion['discount'], 2));
        }

        $endDate = (new DateTimeImmutable($startDate))
            ->modify('+' . (int) $package['duration_days'] . ' days')
            ->format('Y-m-d');

        $subscriptionModel = new MemberSubscription();
        $subscriptionId = $subscriptionModel->create([
            'member_id' => $memberId,
            'package_id' => (int) $package['id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price_paid' => $pricePaid,
            'created_by' => (int) Auth::user()['id'],
        ]);

        $stmt = Database::connection()->prepare(
            'INSERT INTO payments (member_id, subscription_id, type, amount, method, status, paid_at, recorded_by)
             VALUES (:member_id, :subscription_id, "membership", :amount, :method, "completed", NOW(), :recorded_by)'
        );
        $stmt->execute([
            'member_id' => $memberId,
            'subscription_id' => $subscriptionId,
            'amount' => $pricePaid,
            'method' => $paymentMethod,
            'recorded_by' => (int) Auth::user()['id'],
        ]);

        if ($promotion) {
            (new Promotion())->recordUsage((int) $promotion['promo']['id'], $memberId, null, $subscriptionId);
        }

        (new Member())->update($memberId, ['status' => 'active']);

        return true;
    }

    private function collectMemberData(): array
    {
        return [
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
            'trainer_id' => $this->input('trainer_id') !== '' ? (int) $this->input('trainer_id') : null,
            'locker_number' => $this->input('locker_number') ?: null,
            'status' => $this->input('status', 'active'),
        ];
    }
}
