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
        ]);
    }
}
