<?php

final class MembershipAdminController extends AdminController
{
    public function index(): void
    {
        $packageModel = new Package();

        $this->adminView('packages/index', [
            'pageTitle' => 'Membership Packages',
            'packages' => $packageModel->allForAdmin(),
        ]);
    }

    public function create(): void
    {
        $this->adminView('packages/form', [
            'pageTitle' => 'Add Package',
            'package' => null,
            'features' => [],
            'badges' => Package::BADGES,
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $data = $this->collectFormData();
        $packageModel = new Package();

        $error = $this->validate($data, $packageModel, null);
        if ($error) {
            flash('danger', $error);
            redirect('admin/packages/create');
        }

        $data['slug'] = $this->uniqueSlug($packageModel, $data['name']);

        $imagePath = Upload::handle($_FILES['image'] ?? [], 'packages');
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        $id = $packageModel->create($data);
        (new MembershipPackageFeature())->replaceAll($id, $_POST['features'] ?? []);
        $this->logActivity('package_created', "Created membership package #$id: {$data['name']}");

        flash('success', 'Package added successfully.');
        redirect('admin/packages/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $packageModel = new Package();
        $package = $packageModel->find((int) $id);
        if (!$package) {
            $this->abort404();
        }

        $this->adminView('packages/form', [
            'pageTitle' => 'Edit Package',
            'package' => $package,
            'features' => (new MembershipPackageFeature())->forPackage((int) $id),
            'badges' => Package::BADGES,
        ]);
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $packageModel = new Package();
        $package = $packageModel->find((int) $id);
        if (!$package) {
            $this->abort404();
        }

        $data = $this->collectFormData();

        $error = $this->validate($data, $packageModel, (int) $id);
        if ($error) {
            flash('danger', $error);
            redirect('admin/packages/' . $id . '/edit');
        }

        $submittedSlug = $this->input('slug');
        if ($submittedSlug !== '' && $submittedSlug !== $package['slug']) {
            if ($packageModel->slugExists($submittedSlug, (int) $id)) {
                flash('danger', 'That URL slug is already used by another package.');
                redirect('admin/packages/' . $id . '/edit');
            }
            $data['slug'] = $submittedSlug;
        }

        $imagePath = Upload::handle($_FILES['image'] ?? [], 'packages');
        if ($imagePath) {
            Upload::delete($package['image']);
            $data['image'] = $imagePath;
        }

        $packageModel->update((int) $id, $data);
        (new MembershipPackageFeature())->replaceAll((int) $id, $_POST['features'] ?? []);
        $this->logActivity('package_updated', "Updated membership package #$id: {$data['name']}");

        flash('success', 'Package updated successfully.');
        redirect('admin/packages/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $packageModel = new Package();
        $package = $packageModel->find((int) $id);
        if (!$package) {
            $this->abort404();
        }

        Upload::delete($package['image']);
        $packageModel->delete((int) $id);
        $this->logActivity('package_deleted', "Deleted membership package #$id: {$package['name']}");

        flash('success', 'Package deleted.');
        redirect('admin/packages');
    }

    public function toggleActive(string $id): void
    {
        Security::requireCsrf();
        $this->requireExists($id)->toggleActive((int) $id);
        $this->logActivity('package_active_toggled', "Toggled active/visible for package #$id");
        flash('success', 'Visibility updated.');
        redirect('admin/packages');
    }

    public function toggleFeatured(string $id): void
    {
        Security::requireCsrf();
        $this->requireExists($id)->toggleFeatured((int) $id);
        $this->logActivity('package_featured_toggled', "Toggled featured for package #$id");
        flash('success', 'Featured status updated.');
        redirect('admin/packages');
    }

    public function toggleOffer(string $id): void
    {
        Security::requireCsrf();
        $this->requireExists($id)->toggleOfferEnabled((int) $id);
        $this->logActivity('package_offer_toggled', "Toggled offer for package #$id");
        flash('success', 'Offer status updated.');
        redirect('admin/packages');
    }

    public function reorder(string $id): void
    {
        Security::requireCsrf();

        $direction = $this->input('direction');
        if (!in_array($direction, ['up', 'down'], true)) {
            flash('danger', 'Invalid direction.');
            redirect('admin/packages');
        }

        $this->requireExists($id)->move((int) $id, $direction);
        $this->logActivity('package_reordered', "Moved package #$id $direction");
        flash('success', 'Display order updated.');
        redirect('admin/packages');
    }

    private function requireExists(string $id): Package
    {
        $packageModel = new Package();
        if (!$packageModel->find((int) $id)) {
            $this->abort404();
        }
        return $packageModel;
    }

    private function validate(array $data, Package $packageModel, ?int $excludeId): ?string
    {
        if ($data['name'] === '') {
            return 'Package name is required.';
        }
        if ($data['duration_days'] <= 0) {
            return 'Duration must be at least 1 day.';
        }
        if ($data['regular_price'] <= 0) {
            return 'Regular price must be greater than zero.';
        }
        return $packageModel->validateOfferPrice($data['regular_price'], $data['offer_price']);
    }

    private function collectFormData(): array
    {
        $offerEndDate = $this->input('offer_end_date');
        $badge = $this->input('badge');

        return [
            'name' => $this->input('name'),
            'category' => $this->input('category', 'regular'),
            'duration_days' => (int) $this->input('duration_days', '0'),
            'regular_price' => (float) $this->input('regular_price', '0'),
            'offer_price' => $this->input('offer_price') !== '' ? (float) $this->input('offer_price') : null,
            'offer_start_date' => $this->input('offer_start_date') ?: null,
            'offer_end_date' => $offerEndDate ? $offerEndDate . ' 23:59:59' : null,
            'offer_enabled' => $this->input('offer_enabled') === '1' ? 1 : 0,
            'badge' => in_array($badge, Package::BADGES, true) ? $badge : null,
            'description' => $this->rawInput('description'),
            'includes_trainer' => $this->input('includes_trainer') === '1' ? 1 : 0,
            'includes_locker' => $this->input('includes_locker') === '1' ? 1 : 0,
            'includes_steam' => $this->input('includes_steam') === '1' ? 1 : 0,
            'includes_sauna' => $this->input('includes_sauna') === '1' ? 1 : 0,
            'includes_diet_plan' => $this->input('includes_diet_plan') === '1' ? 1 : 0,
            'is_featured' => $this->input('is_featured') === '1' ? 1 : 0,
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', '0'),
        ];
    }

    private function uniqueSlug(Package $packageModel, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'package';
        $slug = $base;
        $i = 2;
        while ($packageModel->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
