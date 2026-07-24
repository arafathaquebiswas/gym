<?php

final class DeliveryTimeSlotAdminController extends AdminController
{
    protected string $moduleKey = 'orders';

    public function index(): void
    {
        $allSlots = (new DeliveryTimeSlot())->all();

        $this->adminView('delivery-time-slots/index', [
            'pageTitle' => 'Delivery & Pickup Time Slots',
            'deliverySlots' => array_values(array_filter($allSlots, fn ($s) => $s['type'] === 'delivery')),
            'pickupSlots' => array_values(array_filter($allSlots, fn ($s) => $s['type'] === 'pickup')),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $label = $this->input('label');
        $type = $this->input('type');

        if ($label === '') {
            flash('danger', 'Time slot label is required.');
            redirect('admin/delivery-time-slots');
        }
        if (!in_array($type, DeliveryTimeSlot::TYPES, true)) {
            flash('danger', 'Invalid slot type.');
            redirect('admin/delivery-time-slots');
        }

        (new DeliveryTimeSlot())->create([
            'type' => $type,
            'label' => $label,
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', '0'),
        ]);

        $this->logActivity('time_slot_created', "Created $type time slot: $label");
        flash('success', 'Time slot added successfully.');
        redirect('admin/delivery-time-slots');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $slotModel = new DeliveryTimeSlot();
        $slot = $slotModel->find((int) $id);
        if (!$slot) {
            $this->abort404();
        }

        $label = $this->input('label');
        if ($label === '') {
            flash('danger', 'Time slot label is required.');
            redirect('admin/delivery-time-slots');
        }

        $slotModel->update((int) $id, [
            'label' => $label,
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', '0'),
        ]);

        $this->logActivity('time_slot_updated', "Updated time slot #$id: $label");
        flash('success', 'Time slot updated successfully.');
        redirect('admin/delivery-time-slots');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $slotModel = new DeliveryTimeSlot();
        $slot = $slotModel->find((int) $id);
        if (!$slot) {
            $this->abort404();
        }

        $slotModel->delete((int) $id);
        $this->logActivity('time_slot_deleted', "Deleted time slot #$id: {$slot['label']}");

        flash('success', 'Time slot deleted.');
        redirect('admin/delivery-time-slots');
    }
}
