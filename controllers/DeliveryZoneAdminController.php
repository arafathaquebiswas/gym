<?php

final class DeliveryZoneAdminController extends AdminController
{
    protected string $moduleKey = 'orders';

    public function index(): void
    {
        $this->adminView('delivery-zones/index', [
            'pageTitle' => 'Delivery Zones',
            'zones' => (new DeliveryZone())->all(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        $charge = (float) $this->input('charge', '0');

        if ($name === '') {
            flash('danger', 'Zone name is required.');
            redirect('admin/delivery-zones');
        }
        if ($charge < 0) {
            flash('danger', 'Charge cannot be negative.');
            redirect('admin/delivery-zones');
        }

        $zoneModel = new DeliveryZone();
        $zoneModel->create([
            'name' => $name,
            'charge' => $charge,
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', '0'),
        ]);

        $this->logActivity('delivery_zone_created', "Created delivery zone: $name");
        flash('success', 'Delivery zone added successfully.');
        redirect('admin/delivery-zones');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $zoneModel = new DeliveryZone();
        $zone = $zoneModel->find((int) $id);
        if (!$zone) {
            $this->abort404();
        }

        $name = $this->input('name');
        $charge = (float) $this->input('charge', '0');

        if ($name === '') {
            flash('danger', 'Zone name is required.');
            redirect('admin/delivery-zones');
        }
        if ($charge < 0) {
            flash('danger', 'Charge cannot be negative.');
            redirect('admin/delivery-zones');
        }

        $zoneModel->update((int) $id, [
            'name' => $name,
            'charge' => $charge,
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', '0'),
        ]);

        $this->logActivity('delivery_zone_updated', "Updated delivery zone #$id: $name");
        flash('success', 'Delivery zone updated successfully.');
        redirect('admin/delivery-zones');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $zoneModel = new DeliveryZone();
        $zone = $zoneModel->find((int) $id);
        if (!$zone) {
            $this->abort404();
        }

        if ($zoneModel->orderCount((int) $id) > 0) {
            flash('danger', 'Cannot delete a zone that has orders placed against it. Disable it instead.');
            redirect('admin/delivery-zones');
        }

        $zoneModel->delete((int) $id);
        $this->logActivity('delivery_zone_deleted', "Deleted delivery zone #$id: {$zone['name']}");

        flash('success', 'Delivery zone deleted.');
        redirect('admin/delivery-zones');
    }
}
