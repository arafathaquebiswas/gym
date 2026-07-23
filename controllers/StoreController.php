<?php

final class StoreController extends Controller
{
    public function index(): void
    {
        if (!Feature::on('store')) {
            $this->abort404();
        }
        if (!Feature::storeAvailable()) {
            $this->view('store-unavailable', ['pageTitle' => 'Store Unavailable']);
            return;
        }

        $productModel = new Product();
        $categoryModel = new ProductCategory();

        $page = max(1, (int) $this->input('page', '1'));
        $category = $this->input('category') ?: null;
        $brand = $this->input('brand') ?: null;
        $search = $this->input('q') ?: null;
        $inStockOnly = $this->input('in_stock') === '1';
        $sort = $this->input('sort') ?: null;

        $result = $productModel->paginate($page, 12, $category, $search, $inStockOnly, $sort, $brand);

        $this->view('store', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'totalPages' => (int) ceil($result['total'] / $result['per_page']),
            'categories' => $categoryModel->allActiveForStorefront(),
            'brands' => (new Brand())->all(),
            'activeCategory' => $category,
            'activeBrand' => $brand,
            'search' => $search,
            'inStockOnly' => $inStockOnly,
            'sort' => $sort,
            'bestSellerIds' => $productModel->bestSellerIds(),
            'popularIds' => $productModel->popularIds(),
        ]);
    }

    public function bundles(): void
    {
        if (!Feature::on('store')) {
            $this->abort404();
        }
        if (!Feature::storeAvailable()) {
            $this->view('store-unavailable', ['pageTitle' => 'Store Unavailable']);
            return;
        }

        $bundleModel = new Bundle();
        $bundles = array_map(fn ($bundle) => [
            'bundle' => $bundle,
            'items' => $bundleModel->itemsFor((int) $bundle['id']),
        ], $bundleModel->allActive());

        $this->view('bundles', [
            'pageTitle' => 'Bundle Deals',
            'bundles' => $bundles,
        ]);
    }

    public function show(string $slug): void
    {
        if (!Feature::on('store')) {
            $this->abort404();
        }
        if (!Feature::storeAvailable()) {
            $this->view('store-unavailable', ['pageTitle' => 'Store Unavailable']);
            return;
        }

        $productModel = new Product();
        $product = $productModel->findBySlug($slug);

        if (!$product) {
            $this->abort404();
        }

        $reviewModel = new ProductReview();
        $member = null;
        $canReview = false;
        if (Auth::hasRole('member')) {
            $member = (new Member())->findByUserId((int) Auth::user()['id']);
            $canReview = $member && $reviewModel->canReview((int) $member['id'], (int) $product['id']);
        }

        $this->view('store-detail', [
            'product' => $product,
            'images' => (new ProductImage())->forProduct((int) $product['id']),
            'reviews' => $reviewModel->forProduct((int) $product['id']),
            'ratingSummary' => $reviewModel->averageRating((int) $product['id']),
            'canReview' => $canReview,
            'relatedProducts' => $productModel->relatedProducts((int) $product['id'], (int) $product['category_id']),
            'frequentlyBoughtWith' => $productModel->frequentlyBoughtWith((int) $product['id']),
            'inWishlist' => Auth::hasRole('member') ? (new Wishlist())->has((int) Auth::user()['id'], (int) $product['id']) : false,
            'isBestSeller' => in_array((int) $product['id'], $productModel->bestSellerIds(20), true),
            'isPopular' => in_array((int) $product['id'], $productModel->popularIds(20), true),
        ]);
    }

    public function submitReview(string $slug): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        if (!Feature::on('reviews')) {
            $this->abort404();
        }

        $product = (new Product())->findBySlug($slug);
        if (!$product) {
            $this->abort404();
        }

        $member = (new Member())->findByUserId((int) Auth::user()['id']);
        $reviewModel = new ProductReview();

        if (!$member || !$reviewModel->canReview((int) $member['id'], (int) $product['id'])) {
            flash('danger', 'You can only review products you have purchased and haven\'t already reviewed.');
            redirect('store/' . $slug);
        }

        $rating = max(1, min(5, (int) $this->input('rating', '5')));
        $comment = $this->rawInput('comment');

        $reviewId = $reviewModel->create((int) $product['id'], (int) $member['id'], null, $rating, $comment);

        $photoModel = new ProductReviewPhoto();
        $files = $_FILES['photos'] ?? null;
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if ($name === '') {
                    continue;
                }
                $file = [
                    'name' => $files['name'][$i], 'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i],
                ];
                $path = Upload::handle($file, 'reviews');
                if ($path) {
                    $photoModel->add($reviewId, $path);
                }
            }
        }

        flash('success', 'Thanks for your review! It will appear publicly once approved by our team.');
        redirect('store/' . $slug);
    }

    public function notifyBackInStock(string $slug): void
    {
        Security::requireCsrf();

        $product = (new Product())->findBySlug($slug);
        if (!$product) {
            $this->abort404();
        }

        $validator = new Validator(['email' => $this->input('email')]);
        $validator->required('email', 'Email')->email('email');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('store/' . $slug);
        }

        (new StockNotification())->subscribe((int) $product['id'], $this->input('email'));

        flash('success', "We'll email you as soon as {$product['name']} is back in stock.");
        redirect('store/' . $slug);
    }

    public function toggleWishlist(string $slug): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        if (!Feature::on('wishlist')) {
            $this->abort404();
        }

        $product = (new Product())->findBySlug($slug);
        if (!$product) {
            $this->abort404();
        }

        $wishlistModel = new Wishlist();
        $userId = (int) Auth::user()['id'];

        if ($wishlistModel->has($userId, (int) $product['id'])) {
            $wishlistModel->remove($userId, (int) $product['id']);
            flash('success', 'Removed from your wishlist.');
        } else {
            $wishlistModel->add($userId, (int) $product['id']);
            flash('success', 'Added to your wishlist.');
        }

        redirect('store/' . $slug);
    }
}
