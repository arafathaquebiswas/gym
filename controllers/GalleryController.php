<?php

final class GalleryController extends Controller
{
    public function index(): void
    {
        if (!Feature::on('gallery')) {
            $this->abort404();
        }

        $galleryModel = new GalleryItem();
        $category = $this->input('category') ?: null;

        $this->view('gallery', [
            'items' => $galleryModel->all($category),
            'activeCategory' => $category,
        ]);
    }
}
