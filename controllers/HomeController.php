<?php

final class HomeController extends Controller
{
    public function index(): void
    {
        $packageModel = new Package();
        $productModel = new Product();
        $trainerModel = new Trainer();
        $galleryModel = new GalleryItem();
        $testimonialModel = new Testimonial();
        $promotionModel = new Promotion();
        $faqModel = new Faq();
        $settingModel = new Setting();

        $this->view('home', [
            'packages' => $packageModel->allActive(),
            'products' => $productModel->featured(8),
            'trainers' => $trainerModel->allActive(),
            'galleryItems' => $galleryModel->recent(8),
            'testimonials' => $testimonialModel->approved(6),
            'promotions' => $promotionModel->active(),
            'faqs' => $faqModel->allActive(),
            'settings' => $settingModel->all(),
            'showFreeTrial' => $this->freeTrialAvailable($settingModel),
        ]);
    }

    private function freeTrialAvailable(Setting $settingModel): bool
    {
        if (!$settingModel->getBool('free_trial_enabled')) {
            return false;
        }

        $today = date('Y-m-d');
        $startDate = $settingModel->get('free_trial_start_date');
        $endDate = $settingModel->get('free_trial_end_date');
        if ($startDate && $today < $startDate) {
            return false;
        }
        if ($endDate && $today > $endDate) {
            return false;
        }

        $max = $settingModel->getInt('free_trial_max_registrations', 0);
        if ($max > 0 && (new FreeTrialRegistration())->count() >= $max) {
            return false;
        }

        return true;
    }
}
