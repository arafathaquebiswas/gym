<?php

final class CartController extends Controller
{
    public function index(): void
    {
        if (!Feature::on('store')) {
            $this->abort404();
        }

        [$userId, $cartToken] = $this->identity();
        $productModel = new Product();
        $lines = array_map([$productModel, 'withComputedOffer'], (new Cart())->forIdentity($userId, $cartToken));

        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['display_price'] * (int) $line['qty'];
        }

        $this->view('cart', [
            'pageTitle' => 'Your Cart',
            'lines' => $lines,
            'subtotal' => $subtotal,
        ]);
    }

    public function add(): void
    {
        Security::requireCsrf();

        if (!Feature::on('store')) {
            $this->abort404();
        }

        $productId = (int) $this->input('product_id');
        $qty = max(1, (int) $this->input('qty', '1'));
        $product = (new Product())->find($productId);

        if (!$product || $product['status'] !== 'published') {
            flash('danger', 'That product is not available.');
            redirect('store');
        }

        [$userId, $cartToken] = $this->identity();
        $cartModel = new Cart();
        $existingQty = 0;
        foreach ($cartModel->forIdentity($userId, $cartToken) as $line) {
            if ((int) $line['id'] === $productId) {
                $existingQty = (int) $line['qty'];
            }
        }

        if (($existingQty + $qty) > $product['stock_qty'] && !($product['allow_preorder'] && Feature::on('preorder'))) {
            flash('danger', "Only {$product['stock_qty']} of {$product['name']} available.");
            redirect('store/' . $product['slug']);
        }

        $cartModel->add($userId, $cartToken, $productId, $qty);

        if ($this->input('buy_now') === '1') {
            redirect('checkout');
        }

        flash('success', $product['name'] . ' added to your cart.');
        redirect($this->input('redirect_to') ?: 'cart');
    }

    public function update(): void
    {
        Security::requireCsrf();

        [$userId, $cartToken] = $this->identity();
        $productId = (int) $this->input('product_id');
        $qty = (int) $this->input('qty', '1');

        if ($qty <= 0) {
            (new Cart())->remove($userId, $cartToken, $productId);
        } else {
            $product = (new Product())->find($productId);
            if ($product && $qty > $product['stock_qty'] && !($product['allow_preorder'] && Feature::on('preorder'))) {
                flash('danger', "Only {$product['stock_qty']} of {$product['name']} available.");
                redirect('cart');
            }
            (new Cart())->updateQty($userId, $cartToken, $productId, $qty);
        }

        redirect('cart');
    }

    public function remove(): void
    {
        Security::requireCsrf();

        [$userId, $cartToken] = $this->identity();
        (new Cart())->remove($userId, $cartToken, (int) $this->input('product_id'));

        flash('success', 'Item removed from cart.');
        redirect('cart');
    }

    private function identity(): array
    {
        $identity = Cart::identity();
        return [$identity['user_id'], $identity['cart_token']];
    }
}
