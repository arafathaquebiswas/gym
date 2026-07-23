<?php

final class CouponAdminController extends AdminController
{
    public function index(): void
    {
        $promotionModel = new Promotion();

        $filters = ['search' => $this->input('search'), 'status' => $this->input('status')];
        $page = max(1, (int) $this->input('page', '1'));
        $result = $promotionModel->paginateForAdmin($filters, $page);

        $this->adminView('coupons/index', [
            'pageTitle' => 'Coupons',
            'coupons' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->adminView('coupons/form', ['pageTitle' => 'Add Coupon', 'coupon' => null]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $data = $this->collectFormData();
        $promotionModel = new Promotion();

        $error = $this->validateCoupon($data, $promotionModel, null);
        if ($error) {
            flash('danger', $error);
            redirect('admin/coupons/create');
        }

        $id = $promotionModel->create($data);
        $this->logActivity('coupon_created', "Created coupon #$id: {$data['code']}");

        flash('success', 'Coupon created successfully.');
        redirect('admin/coupons/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $promotionModel = new Promotion();
        $coupon = $promotionModel->find((int) $id);
        if (!$coupon) {
            $this->abort404();
        }

        $this->adminView('coupons/form', ['pageTitle' => 'Edit Coupon', 'coupon' => $coupon]);
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $promotionModel = new Promotion();
        $coupon = $promotionModel->find((int) $id);
        if (!$coupon) {
            $this->abort404();
        }

        $data = $this->collectFormData();

        $error = $this->validateCoupon($data, $promotionModel, (int) $id);
        if ($error) {
            flash('danger', $error);
            redirect('admin/coupons/' . $id . '/edit');
        }

        $promotionModel->update((int) $id, $data);
        $this->logActivity('coupon_updated', "Updated coupon #$id: {$data['code']}");

        flash('success', 'Coupon updated successfully.');
        redirect('admin/coupons/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $promotionModel = new Promotion();
        $coupon = $promotionModel->find((int) $id);
        if (!$coupon) {
            $this->abort404();
        }

        $promotionModel->delete((int) $id);
        $this->logActivity('coupon_deleted', "Deleted coupon #$id: {$coupon['code']}");

        flash('success', 'Coupon deleted.');
        redirect('admin/coupons');
    }

    public function toggleActive(string $id): void
    {
        Security::requireCsrf();

        $promotionModel = new Promotion();
        if (!$promotionModel->find((int) $id)) {
            $this->abort404();
        }

        $promotionModel->toggleActive((int) $id);
        $this->logActivity('coupon_toggled', "Toggled active status for coupon #$id");

        flash('success', 'Status updated.');
        redirect('admin/coupons');
    }

    public function duplicate(string $id): void
    {
        Security::requireCsrf();

        $promotionModel = new Promotion();
        if (!$promotionModel->find((int) $id)) {
            $this->abort404();
        }

        $newId = $promotionModel->duplicate((int) $id);
        $this->logActivity('coupon_duplicated', "Duplicated coupon #$id as #$newId");

        flash('success', 'Coupon duplicated — review and activate it when ready.');
        redirect('admin/coupons/' . $newId . '/edit');
    }

    private function validateCoupon(array $data, Promotion $promotionModel, ?int $excludeId): ?string
    {
        if ($data['title'] === '') {
            return 'Coupon name is required.';
        }
        if ($data['code'] === '') {
            return 'Coupon code is required.';
        }
        if ($promotionModel->codeExists($data['code'], $excludeId)) {
            return 'That coupon code is already in use.';
        }
        if ($data['discount_value'] <= 0) {
            return 'Discount value must be greater than zero.';
        }
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return 'Percentage discount cannot exceed 100%.';
        }
        if (!$data['start_date'] || !$data['end_date']) {
            return 'Start date and expiry date are required.';
        }
        if ($data['end_date'] < $data['start_date']) {
            return 'Expiry date must be on or after the start date.';
        }
        return null;
    }

    private function collectFormData(): array
    {
        return [
            'title' => $this->input('title'),
            'code' => strtoupper($this->input('code')),
            'description' => $this->rawInput('description'),
            'discount_type' => in_array($this->input('discount_type'), ['percent', 'fixed'], true) ? $this->input('discount_type') : 'percent',
            'discount_value' => (float) $this->input('discount_value', '0'),
            'max_discount_amount' => $this->input('max_discount_amount') !== '' ? (float) $this->input('max_discount_amount') : null,
            'applies_to' => in_array($this->input('applies_to'), ['product', 'membership', 'trainer', 'both'], true) ? $this->input('applies_to') : 'both',
            'min_purchase' => (float) $this->input('min_purchase', '0'),
            'usage_limit' => $this->input('usage_limit') !== '' ? (int) $this->input('usage_limit') : null,
            'per_customer_limit' => $this->input('per_customer_limit') !== '' ? (int) $this->input('per_customer_limit') : null,
            'start_date' => $this->input('start_date') ?: null,
            'end_date' => $this->input('end_date') ?: null,
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
        ];
    }
}
