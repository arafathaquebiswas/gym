<?php

final class ReviewAdminController extends AdminController
{
    protected string $moduleKey = 'reviews';

    public function index(): void
    {
        $reviewModel = new ProductReview();

        $filters = [
            'rating' => $this->input('rating'),
            'status' => $this->input('status'),
            'product_id' => $this->input('product_id'),
            'sort' => $this->input('sort'),
        ];
        $page = max(1, (int) $this->input('page', '1'));
        $result = $reviewModel->paginateForAdmin($filters, $page);

        $this->adminView('reviews/index', [
            'pageTitle' => 'Reviews',
            'reviews' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
        ]);
    }

    public function approve(string $id): void
    {
        Security::requireCsrf();
        $this->requireExists($id)->setStatus((int) $id, 'approved');
        $this->logActivity('review_approved', "Approved review #$id");
        flash('success', 'Review approved.');
        redirect('admin/reviews');
    }

    public function hide(string $id): void
    {
        Security::requireCsrf();
        $this->requireExists($id)->setStatus((int) $id, 'hidden');
        $this->logActivity('review_hidden', "Hid review #$id");
        flash('success', 'Review hidden.');
        redirect('admin/reviews');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();
        $review = (new ProductReview())->find((int) $id);
        if (!$review) {
            $this->abort404();
        }
        (new ProductReview())->delete((int) $id);
        $this->logActivity('review_deleted', "Deleted review #$id for {$review['product_name']}");
        flash('success', 'Review deleted.');
        redirect('admin/reviews');
    }

    public function reply(string $id): void
    {
        Security::requireCsrf();
        $reviewModel = $this->requireExists($id);

        $reply = $this->rawInput('admin_reply');
        if ($reply === '') {
            flash('danger', 'Reply cannot be empty.');
            redirect('admin/reviews');
        }

        $reviewModel->reply((int) $id, $reply);
        $this->logActivity('review_replied', "Replied to review #$id");
        flash('success', 'Reply posted.');
        redirect('admin/reviews');
    }

    private function requireExists(string $id): ProductReview
    {
        $reviewModel = new ProductReview();
        if (!$reviewModel->find((int) $id)) {
            $this->abort404();
        }
        return $reviewModel;
    }
}
