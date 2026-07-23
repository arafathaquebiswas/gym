<?php

final class PosController extends AdminController
{
    public function index(): void
    {
        $this->adminView('pos/index', [
            'pageTitle' => 'Point of Sale',
            'productsJson' => json_encode((new Product())->allActiveInStock()),
        ]);
    }

    public function checkout(): void
    {
        Security::requireCsrf();

        $cartInput = json_decode($this->rawInput('cart_json', '[]'), true);
        if (!is_array($cartInput) || !$cartInput) {
            flash('danger', 'Your cart is empty.');
            redirect('admin/pos');
        }

        $productModel = new Product();
        $cart = [];

        foreach ($cartInput as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['qty'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            // Never trust client-submitted prices/stock — always re-read live from the DB.
            $product = $productModel->find($productId);
            if (!$product || !$product['is_active']) {
                flash('danger', 'One of the items in your cart is no longer available.');
                redirect('admin/pos');
            }
            if ($product['stock_qty'] < $qty) {
                flash('danger', "Not enough stock for {$product['name']} (only {$product['stock_qty']} left).");
                redirect('admin/pos');
            }

            $cart[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'unit_price' => (float) $product['display_price'],
            ];
        }

        if (!$cart) {
            flash('danger', 'Your cart is empty.');
            redirect('admin/pos');
        }

        $memberIdInput = $this->input('member_id');
        $memberId = $memberIdInput !== '' ? (int) $memberIdInput : null;
        $discount = (float) $this->input('discount', '0');
        $paymentMethod = $this->input('payment_method', 'cash');
        $couponCode = $this->input('coupon_code') ?: null;

        try {
            $result = (new Sale())->create($cart, $memberId, $discount, $paymentMethod, $couponCode, (int) Auth::user()['id']);
        } catch (RuntimeException $e) {
            flash('danger', $e->getMessage());
            redirect('admin/pos');
        }

        $this->logActivity('pos_sale_completed', "Completed sale {$result['invoice_no']}");
        flash('success', 'Sale completed — invoice ' . $result['invoice_no']);
        redirect('admin/pos/receipt/' . $result['id']);
    }

    public function receipt(string $id): void
    {
        $sale = (new Sale())->find((int) $id);
        if (!$sale) {
            $this->abort404();
        }

        $this->adminView('pos/receipt', [
            'pageTitle' => 'Receipt — ' . $sale['invoice_no'],
            'sale' => $sale,
            'items' => (new SaleItem())->forSale((int) $id),
        ]);
    }

    public function pdf(string $id): void
    {
        $sale = (new Sale())->find((int) $id);
        if (!$sale) {
            $this->abort404();
        }

        $items = (new SaleItem())->forSale((int) $id);
        $pdf = Invoice::generate($sale, $items);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $sale['invoice_no'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}
