<?php

final class CartController extends Controller
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

        [$userId, $cartToken] = $this->identity();
        $productModel = new Product();
        $lines = array_map([$productModel, 'withComputedOffer'], (new Cart())->forIdentity($userId, $cartToken));

        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['display_price'] * (int) $line['qty'];
        }

        $cartLines = array_map(fn ($l) => ['product_id' => (int) $l['id'], 'qty' => (int) $l['qty']], $lines);

        $this->view('cart', [
            'pageTitle' => 'Your Cart',
            'lines' => $lines,
            'subtotal' => $subtotal,
            'bundleMatches' => (new Bundle())->matchFor($cartLines),
        ]);
    }

    public function add(): void
    {
        Security::requireCsrf();

        if (!Feature::on('store')) {
            $this->abort404();
        }
        if (!Feature::storeAvailable()) {
            flash('danger', 'The store is temporarily unavailable.');
            redirect('store');
        }

        $productId = (int) $this->input('product_id');
        $qty = max(1, (int) $this->input('qty', '1'));
        $product = (new Product())->find($productId);

        if (!$product || $product['status'] !== 'published') {
            flash('danger', 'That product is not available.');
            redirect('store');
        }

        // The variant is re-read from the database rather than taken on trust: the form
        // posts only an id, so price and stock must come from the row it points at, and
        // that row has to belong to this product and still be active.
        $variantId = (int) $this->input('variant_id');
        $variant = null;
        $variantModel = new ProductVariant();
        $activeVariants = $variantModel->activeForProduct($productId);

        if ($variantId > 0) {
            foreach ($activeVariants as $candidate) {
                if ((int) $candidate['id'] === $variantId) {
                    $variant = $candidate;
                    break;
                }
            }
            if (!$variant) {
                flash('danger', 'That option is no longer available. Please choose another.');
                redirect('store/' . $product['slug']);
            }
        } elseif ($activeVariants !== []) {
            flash('danger', 'Please choose a weight before adding to cart.');
            redirect('store/' . $product['slug']);
        }

        $label = $variant ? ProductVariant::label($variant) : null;
        $availableStock = $variant ? (int) $variant['stock_qty'] : (int) $product['stock_qty'];

        [$userId, $cartToken] = $this->identity();
        $cartModel = new Cart();
        $existingQty = 0;
        foreach ($cartModel->forIdentity($userId, $cartToken) as $line) {
            if ((int) $line['id'] === $productId && (int) $line['variant_id'] === $variantId) {
                $existingQty = (int) $line['qty'];
            }
        }

        if (($existingQty + $qty) > $availableStock && !($product['allow_preorder'] && Feature::on('preorder'))) {
            $what = $product['name'] . ($label ? " ($label)" : '');
            flash('danger', "Only $availableStock of $what available.");
            redirect('store/' . $product['slug']);
        }

        $cartModel->add($userId, $cartToken, $productId, $qty, $variantId);

        if ($this->input('buy_now') === '1') {
            redirect('checkout');
        }

        flash('success', $product['name'] . ($label ? " ($label)" : '') . ' added to your cart.');
        redirect($this->input('redirect_to') ?: 'cart');
    }

    public function addBundle(string $id): void
    {
        Security::requireCsrf();

        if (!Feature::on('store')) {
            $this->abort404();
        }
        if (!Feature::storeAvailable()) {
            flash('danger', 'The store is temporarily unavailable.');
            redirect('store');
        }

        $bundleModel = new Bundle();
        $bundle = $bundleModel->find((int) $id);
        if (!$bundle) {
            $this->abort404();
        }

        [$userId, $cartToken] = $this->identity();
        $cartModel = new Cart();
        foreach ($bundleModel->itemsFor((int) $id) as $item) {
            $cartModel->add($userId, $cartToken, (int) $item['product_id'], (int) $item['qty']);
        }

        flash('success', "Added the {$bundle['name']} bundle to your cart — the bundle price applies automatically.");
        redirect('cart');
    }

    public function update(): void
    {
        Security::requireCsrf();

        [$userId, $cartToken] = $this->identity();
        $cartModel = new Cart();
        // Lines are addressed by cart row id — one product can now be several lines,
        // one per weight, so a product id no longer picks out a single one.
        $cartId = (int) $this->input('cart_id');
        $qty = (int) $this->input('qty', '1');

        if ($qty <= 0) {
            $cartModel->remove($userId, $cartToken, $cartId);
            redirect('cart');
        }

        $line = $cartModel->findLine($userId, $cartToken, $cartId);
        if (!$line) {
            redirect('cart');
        }

        // forIdentity() has already folded the variant's stock over the product's.
        if ($qty > (int) $line['stock_qty'] && !($line['allow_preorder'] && Feature::on('preorder'))) {
            $what = $line['name'] . ($line['variant_label'] ? " ({$line['variant_label']})" : '');
            flash('danger', "Only {$line['stock_qty']} of $what available.");
            redirect('cart');
        }

        $cartModel->updateQty($userId, $cartToken, $cartId, $qty);
        redirect('cart');
    }

    public function remove(): void
    {
        Security::requireCsrf();

        [$userId, $cartToken] = $this->identity();
        (new Cart())->remove($userId, $cartToken, (int) $this->input('cart_id'));

        flash('success', 'Item removed from cart.');
        redirect('cart');
    }

    private function identity(): array
    {
        $identity = Cart::identity();
        return [$identity['user_id'], $identity['cart_token']];
    }
}
