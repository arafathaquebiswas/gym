<?php

final class TrainerAdminController extends AdminController
{
    public function index(): void
    {
        $trainerModel = new Trainer();

        $filters = [
            'search' => $this->input('search'),
            'status' => $this->input('status'),
            'specialization' => $this->input('specialization'),
            'sort' => $this->input('sort'),
        ];
        $page = max(1, (int) $this->input('page', '1'));

        $result = $trainerModel->paginateForAdmin($filters, $page);

        $this->adminView('trainers/index', [
            'pageTitle' => 'Trainers',
            'trainers' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
            'specializations' => $trainerModel->distinctSpecializations(),
            'stats' => $trainerModel->adminStatistics(),
        ]);
    }

    public function create(): void
    {
        $this->adminView('trainers/form', [
            'pageTitle' => 'Add Trainer',
            'trainer' => null,
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $data = $this->collectFormData();

        $validator = new Validator($data);
        $validator->required('name', 'Name')
            ->email('email', 'Email')
            ->phone('phone', 'Phone');

        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('admin/trainers/create');
        }

        $trainerModel = new Trainer();

        $offerError = $trainerModel->validateOfferPrice($data['monthly_pt_price'], $data['offer_price']);
        if ($offerError !== null) {
            flash('danger', $offerError);
            redirect('admin/trainers/create');
        }

        $data['slug'] = $this->uniqueSlug($trainerModel, $data['name']);

        $photoPath = Upload::handle($_FILES['photo'] ?? [], 'trainers');
        if ($photoPath) {
            $data['photo'] = $photoPath;
        }
        $coverPath = Upload::handle($_FILES['cover_photo'] ?? [], 'trainers');
        if ($coverPath) {
            $data['cover_photo'] = $coverPath;
        }

        $id = $trainerModel->create($data);
        $this->logActivity('trainer_created', "Created trainer #$id: {$data['name']}");

        flash('success', 'Trainer added successfully.');
        redirect('admin/trainers/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $trainerModel = new Trainer();
        $trainer = $trainerModel->find((int) $id);
        if (!$trainer) {
            $this->abort404();
        }

        $this->adminView('trainers/form', [
            'pageTitle' => 'Edit Trainer',
            'trainer' => $trainer,
            'weeklySchedule' => (new TrainerSchedule())->weeklyFor((int) $id),
            'gallery' => (new TrainerGallery())->forTrainer((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $trainerModel = new Trainer();
        $trainer = $trainerModel->find((int) $id);
        if (!$trainer) {
            $this->abort404();
        }

        $data = $this->collectFormData();

        $validator = new Validator($data);
        $validator->required('name', 'Name')
            ->email('email', 'Email')
            ->phone('phone', 'Phone');

        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('admin/trainers/' . $id . '/edit');
        }

        $offerError = $trainerModel->validateOfferPrice($data['monthly_pt_price'], $data['offer_price']);
        if ($offerError !== null) {
            flash('danger', $offerError);
            redirect('admin/trainers/' . $id . '/edit');
        }

        $submittedSlug = $this->input('slug');
        if ($submittedSlug !== '' && $submittedSlug !== $trainer['slug']) {
            if ($trainerModel->slugExists($submittedSlug, (int) $id)) {
                flash('danger', 'That URL slug is already used by another trainer.');
                redirect('admin/trainers/' . $id . '/edit');
            }
            $data['slug'] = $submittedSlug;
        }

        $photoPath = Upload::handle($_FILES['photo'] ?? [], 'trainers');
        if ($photoPath) {
            Upload::delete($trainer['photo']);
            $data['photo'] = $photoPath;
        }
        $coverPath = Upload::handle($_FILES['cover_photo'] ?? [], 'trainers');
        if ($coverPath) {
            Upload::delete($trainer['cover_photo']);
            $data['cover_photo'] = $coverPath;
        }

        $trainerModel->update((int) $id, $data);

        $scheduleInput = $_POST['schedule'] ?? [];
        if (is_array($scheduleInput)) {
            (new TrainerSchedule())->saveWeek((int) $id, $scheduleInput);
        }

        $this->logActivity('trainer_updated', "Updated trainer #$id: {$data['name']}");

        flash('success', 'Trainer updated successfully.');
        redirect('admin/trainers/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $trainerModel = new Trainer();
        $trainer = $trainerModel->find((int) $id);
        if (!$trainer) {
            $this->abort404();
        }

        Upload::delete($trainer['photo']);
        Upload::delete($trainer['cover_photo']);
        foreach ((new TrainerGallery())->forTrainer((int) $id) as $image) {
            Upload::delete($image['image_path']);
        }

        $trainerModel->delete((int) $id);
        $this->logActivity('trainer_deleted', "Deleted trainer #$id: {$trainer['name']}");

        flash('success', 'Trainer deleted.');
        redirect('admin/trainers');
    }

    public function bulkAction(): void
    {
        Security::requireCsrf();

        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $action = $this->input('bulk_action');

        if (!$ids) {
            flash('danger', 'No trainers selected.');
            redirect('admin/trainers');
        }

        $trainerModel = new Trainer();
        $count = 0;

        foreach ($ids as $id) {
            $trainer = $trainerModel->find($id);
            if (!$trainer) {
                continue;
            }

            switch ($action) {
                case 'activate':
                    $trainerModel->update($id, ['is_active' => 1]);
                    $count++;
                    break;
                case 'deactivate':
                    $trainerModel->update($id, ['is_active' => 0]);
                    $count++;
                    break;
                case 'delete':
                    Upload::delete($trainer['photo']);
                    Upload::delete($trainer['cover_photo']);
                    foreach ((new TrainerGallery())->forTrainer($id) as $image) {
                        Upload::delete($image['image_path']);
                    }
                    $trainerModel->delete($id);
                    $count++;
                    break;
            }
        }

        $this->logActivity('trainers_bulk_action', "Bulk-$action on $count trainer(s)");
        flash('success', "$count trainer(s) updated.");
        redirect('admin/trainers');
    }

    public function show(string $id): void
    {
        $trainerModel = new Trainer();
        $trainer = $trainerModel->find((int) $id);
        if (!$trainer) {
            $this->abort404();
        }

        $this->adminView('trainers/show', [
            'pageTitle' => $trainer['name'],
            'trainer' => $trainer,
            'assignedMemberCount' => $trainerModel->assignedMemberCount((int) $id),
            'bookings' => (new TrainerBooking())->upcomingForTrainer((int) $id),
            'reviewCount' => (new TrainerReview())->count((int) $id),
            'averageRating' => (new TrainerReview())->averageRating((int) $id),
        ]);
    }

    public function changeStatus(string $id): void
    {
        Security::requireCsrf();

        $status = $this->input('status');
        if (!in_array($status, ['available', 'busy', 'on_leave', 'offline'], true)) {
            flash('danger', 'Invalid status.');
            redirect('admin/trainers');
        }

        $trainerModel = new Trainer();
        if (!$trainerModel->find((int) $id)) {
            $this->abort404();
        }

        $trainerModel->update((int) $id, ['availability_status' => $status]);
        $this->logActivity('trainer_status_changed', "Trainer #$id status set to $status");

        flash('success', 'Status updated.');
        redirect('admin/trainers');
    }

    public function featured(string $id): void
    {
        Security::requireCsrf();

        $trainerModel = new Trainer();
        if (!$trainerModel->find((int) $id)) {
            $this->abort404();
        }

        $trainerModel->toggleFeatured((int) $id);
        $this->logActivity('trainer_featured_toggled', "Toggled featured for trainer #$id");

        flash('success', 'Featured status updated.');
        redirect('admin/trainers');
    }

    public function toggleActive(string $id): void
    {
        Security::requireCsrf();

        $trainerModel = new Trainer();
        if (!$trainerModel->find((int) $id)) {
            $this->abort404();
        }

        $trainerModel->toggleActive((int) $id);
        $this->logActivity('trainer_active_toggled', "Toggled active/visible for trainer #$id");

        flash('success', 'Visibility updated.');
        redirect('admin/trainers');
    }

    public function reorder(string $id): void
    {
        Security::requireCsrf();

        $direction = $this->input('direction');
        if (!in_array($direction, ['up', 'down'], true)) {
            flash('danger', 'Invalid direction.');
            redirect('admin/trainers');
        }

        $trainerModel = new Trainer();
        if (!$trainerModel->find((int) $id)) {
            $this->abort404();
        }

        $trainerModel->move((int) $id, $direction);

        $this->logActivity('trainer_reordered', "Moved trainer #$id $direction");
        flash('success', 'Display order updated.');
        redirect('admin/trainers');
    }

    /** Removes the trainer's main profile photo, reverting the website to the default placeholder. */
    public function deletePhoto(string $id): void
    {
        Security::requireCsrf();

        $trainerModel = new Trainer();
        $trainer = $trainerModel->find((int) $id);
        if (!$trainer) {
            $this->abort404();
        }

        Upload::delete($trainer['photo']);
        $trainerModel->update((int) $id, ['photo' => null]);
        $this->logActivity('trainer_photo_deleted', "Removed profile photo for trainer #$id");

        flash('success', 'Profile photo removed.');
        redirect('admin/trainers/' . $id . '/edit');
    }

    public function deleteCoverPhoto(string $id): void
    {
        Security::requireCsrf();

        $trainerModel = new Trainer();
        $trainer = $trainerModel->find((int) $id);
        if (!$trainer) {
            $this->abort404();
        }

        Upload::delete($trainer['cover_photo']);
        $trainerModel->update((int) $id, ['cover_photo' => null]);
        $this->logActivity('trainer_cover_deleted', "Removed cover photo for trainer #$id");

        flash('success', 'Cover photo removed.');
        redirect('admin/trainers/' . $id . '/edit');
    }

    public function gallery(string $id): void
    {
        $trainerModel = new Trainer();
        $trainer = $trainerModel->find((int) $id);
        if (!$trainer) {
            $this->abort404();
        }

        $this->adminView('trainers/gallery', [
            'pageTitle' => 'Photo Gallery — ' . $trainer['name'],
            'trainer' => $trainer,
            'images' => (new TrainerGallery())->forTrainer((int) $id),
        ]);
    }

    /** Accepts one or more files from a multi-file input named "images[]". */
    public function galleryUpload(string $id): void
    {
        Security::requireCsrf();

        $trainerModel = new Trainer();
        if (!$trainerModel->find((int) $id)) {
            $this->abort404();
        }

        $galleryModel = new TrainerGallery();
        $files = $_FILES['images'] ?? null;
        $uploaded = 0;

        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if ($name === '') {
                    continue;
                }
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                $path = Upload::handle($file, 'trainers');
                if ($path) {
                    $galleryModel->add((int) $id, $path);
                    $uploaded++;
                }
            }
        }

        if ($uploaded > 0) {
            $this->logActivity('trainer_gallery_upload', "Added $uploaded gallery photo(s) for trainer #$id");
            flash('success', $uploaded . ' photo(s) added to the gallery.');
        } else {
            flash('danger', Upload::lastError() ?? 'No valid images were uploaded.');
        }

        redirect('admin/trainers/' . $id . '/gallery');
    }

    public function galleryDelete(string $id, string $imageId): void
    {
        Security::requireCsrf();

        $galleryModel = new TrainerGallery();
        $image = $galleryModel->find((int) $imageId);

        if (!$image || (int) $image['trainer_id'] !== (int) $id) {
            $this->abort404();
        }

        Upload::delete($image['image_path']);
        $galleryModel->delete((int) $imageId);
        $this->logActivity('trainer_gallery_delete', "Removed gallery photo #$imageId for trainer #$id");

        flash('success', 'Photo removed from gallery.');
        redirect('admin/trainers/' . $id . '/gallery');
    }

    private function collectFormData(): array
    {
        return [
            'name' => $this->input('name'),
            'job_title' => $this->input('job_title'),
            'gender' => $this->input('gender') ?: null,
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'dob' => $this->input('dob') ?: null,
            'joining_date' => $this->input('joining_date') ?: null,
            'specialization' => $this->input('specialization'),
            'experience_years' => (int) $this->input('experience_years', '0'),
            'certifications' => $this->input('certifications'),
            'achievements' => $this->input('achievements'),
            'languages_spoken' => $this->input('languages_spoken'),
            'bio' => $this->rawInput('bio'),
            'monthly_pt_price' => $this->input('monthly_pt_price') !== '' ? (float) $this->input('monthly_pt_price') : null,
            'hourly_rate' => $this->input('hourly_rate') !== '' ? (float) $this->input('hourly_rate') : null,
            'offer_price' => $this->input('offer_price') !== '' ? (float) $this->input('offer_price') : null,
            'offer_enabled' => $this->input('offer_enabled') === '1' ? 1 : 0,
            'offer_start_date' => $this->input('offer_start_date') ?: null,
            'offer_end_date' => $this->input('offer_end_date') ?: null,
            'max_members' => $this->input('max_members') !== '' ? (int) $this->input('max_members') : null,
            'availability_status' => $this->input('availability_status', 'available'),
            'facebook_url' => $this->input('facebook_url'),
            'instagram_url' => $this->input('instagram_url'),
            'linkedin_url' => $this->input('linkedin_url'),
            'display_order' => (int) $this->input('display_order', '0'),
            'is_featured' => $this->input('is_featured') === '1' ? 1 : 0,
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
        ];
    }

    private function uniqueSlug(Trainer $trainerModel, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'trainer';
        $slug = $base;
        $i = 2;
        while ($trainerModel->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
