<?php

final class AdminDashboardController extends AdminController
{
    public function index(): void
    {
        $trainerModel = new Trainer();
        $trainers = $trainerModel->allForAdmin();

        $this->adminView('dashboard', [
            'pageTitle' => 'Dashboard',
            'trainerCount' => count($trainers),
            'activeTrainerCount' => count(array_filter($trainers, fn ($t) => (int) $t['is_active'] === 1)),
            'featuredTrainerCount' => count(array_filter($trainers, fn ($t) => (int) $t['is_featured'] === 1)),
        ]);
    }
}
