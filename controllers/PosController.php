<?php

final class PosController extends AdminController
{
    protected string $moduleKey = 'pos';

    public function index(): void
    {
        $settingModel = new Setting();

        // The receipt page sends the admin back here with ?completed={saleId}
        // once they have printed or downloaded the invoice. The id is looked up
        // rather than the invoice number being passed in the URL, so the number
        // shown is always a real one and nothing user-supplied reaches the page.
        $completedId = (int) $this->input('completed');
        if ($completedId > 0) {
            $sale = (new Sale())->find($completedId);
            if ($sale) {
                flash('success', 'Sale completed — invoice ' . $sale['invoice_no']);
            }
            // Redirect so the message is not repeated on refresh, and the query
            // string does not linger on the POS screen.
            redirect('admin/pos');
        }

        // Set by checkout() only when a sale was actually created. Consumed here so
        // the browser clears its saved draft exactly once, on the first POS page
        // load after a completed sale — whether the admin came back via the
        // invoice's Print/Download or straight through Back to POS. A checkout
        // that failed never sets it, so the draft survives to be corrected.
        $draftConsumed = !empty($_SESSION['pos_draft_consumed']);
        unset($_SESSION['pos_draft_consumed']);

        $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        $this->adminView('pos/index', [
            'pageTitle' => 'Point of Sale',
            'productsJson' => json_encode((new Product())->allActiveInStock(), $jsonFlags),
            'membersJson' => json_encode((new Member())->allForPicker(), $jsonFlags),
            'taxEnabled' => $settingModel->getBool('tax_enabled'),
            'taxPercent' => (float) $settingModel->get('tax_percent', '0'),
            'draftConsumed' => $draftConsumed,
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
            if (!$product || $product['status'] === 'draft') {
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

        // The sale is now real, so the browser's saved draft is spent. Flagged
        // rather than cleared inline because this response redirects to the
        // invoice — the POS page is what owns the stored draft.
        $_SESSION['pos_draft_consumed'] = true;

        $this->logActivity('pos_sale_completed', "Completed sale {$result['invoice_no']}");

        // No "Sale completed" flash here: the invoice page states the number and
        // status plainly, and the confirmation now belongs on the POS screen the
        // admin lands back on after printing or downloading — see index().
        redirect('admin/pos/receipt/' . $result['id']);
    }

    public function receipt(string $id): void
    {
        $sale = (new Sale())->find((int) $id);
        if (!$sale) {
            $this->abort404();
        }

        $items = $this->saleItemsWithDetails((int) $id);

        $pageTitle = 'Receipt — ' . $sale['invoice_no'];
        extract(['pageTitle' => $pageTitle, 'sale' => $sale, 'items' => $items]);
        require BASE_PATH . '/views/admin/pos/receipt.php';
    }

    public function pdf(string $id): void
    {
        $sale = (new Sale())->find((int) $id);
        if (!$sale) {
            $this->abort404();
        }

        $items = $this->saleItemsWithDetails((int) $id);
        $pdf = Invoice::generate($sale, $items);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $sale['invoice_no'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function saleItemsWithDetails(int $saleId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT si.*, p.name AS product_name, p.sku, p.image AS product_image
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = :sale_id
             ORDER BY si.id ASC'
        );
        $stmt->execute(['sale_id' => $saleId]);
        return $stmt->fetchAll();
    }
}
