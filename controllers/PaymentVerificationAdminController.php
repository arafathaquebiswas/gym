<?php

/**
 * Admin review of online membership payments (bKash/Nagad).
 *
 * A visitor who paid online submits a Transaction ID and a screenshot; this is where
 * an admin compares the two and marks the payment Verified or Rejected. Nothing here
 * activates a membership — activation still happens by attaching a package in the
 * Members screen, which refuses to run until the payment is Verified.
 */
final class PaymentVerificationAdminController extends AdminController
{
    protected string $moduleKey = 'members';

    public function index(): void
    {
        $memberModel = new Member();
        $status = $this->input('status');

        $this->adminView('registrations/index', [
            'pageTitle' => 'Payment Verification',
            'registrations' => $memberModel->paymentQueue($status),
            'counts' => $memberModel->paymentStatusCounts(),
            'activeStatus' => isset(Member::PAYMENT_STATUSES[$status]) ? $status : '',
        ]);
    }

    public function verify(string $id): void
    {
        $member = $this->requirePaymentRegistration($id);

        (new Member())->setPaymentStatus((int) $id, 'verified', (int) Auth::user()['id']);

        $this->logActivity(
            'payment_verified',
            "Verified {$member['payment_method']} payment {$member['transaction_id']} for member #$id ({$member['name']})"
        );
        flash('success', "Payment verified for {$member['name']}. You can now activate the membership from their member page.");
        redirect('admin/registrations');
    }

    public function reject(string $id): void
    {
        $member = $this->requirePaymentRegistration($id);
        $reason = $this->input('rejection_reason') ?: null;

        (new Member())->setPaymentStatus((int) $id, 'rejected', (int) Auth::user()['id'], $reason);

        $this->logActivity(
            'payment_rejected',
            "Rejected {$member['payment_method']} payment {$member['transaction_id']} for member #$id ({$member['name']})"
                . ($reason ? " — $reason" : '')
        );
        flash('success', "Payment rejected for {$member['name']}.");
        redirect('admin/registrations');
    }

    /**
     * Loads a member that actually went through the online-payment flow. A walk-in
     * registration has no payment_status and must not be verifiable — there is no
     * screenshot or Transaction ID behind it to check.
     */
    private function requirePaymentRegistration(string $id): array
    {
        Security::requireCsrf();

        $member = (new Member())->find((int) $id);
        if (!$member || $member['payment_status'] === null) {
            $this->abort404();
        }

        return $member;
    }
}
