<?php

final class StoreController extends Controller
{
    public function index(): void
    {
        $productModel = new Product();
        $categoryModel = new ProductCategory();

        $page = max(1, (int) $this->input('page', '1'));
        $category = $this->input('category') ?: null;
        $search = $this->input('q') ?: null;

        $result = $productModel->paginate($page, 12, $category, $search);

        $this->view('store', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'totalPages' => (int) ceil($result['total'] / $result['per_page']),
            'categories' => $categoryModel->all(),
            'activeCategory' => $category,
            'search' => $search,
        ]);
    }

    public function show(string $slug): void
    {
        $productModel = new Product();
        $product = $productModel->findBySlug($slug);

        if (!$product) {
            $this->abort404();
        }

        $this->view('store-detail', ['product' => $product]);
    }
}
