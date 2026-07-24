<?php

final class ContactMessageAdminController extends AdminController
{
    protected string $moduleKey = 'messages';

    public function index(): void
    {
        $messageModel = new ContactMessage();

        $filters = ['status' => $this->input('status')];
        $page = max(1, (int) $this->input('page', '1'));
        $result = $messageModel->paginateForAdmin($filters, $page);

        $this->adminView('messages/index', [
            'pageTitle' => 'Messages',
            'messages' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
            'newCount' => $messageModel->newCount(),
        ]);
    }

    public function show(string $id): void
    {
        $messageModel = new ContactMessage();
        $message = $messageModel->find((int) $id);
        if (!$message) {
            $this->abort404();
        }

        if ($message['status'] === 'new') {
            $messageModel->setStatus((int) $id, 'read');
            $message['status'] = 'read';
        }

        $this->adminView('messages/show', [
            'pageTitle' => 'Message from ' . $message['name'],
            'message' => $message,
        ]);
    }

    public function markReplied(string $id): void
    {
        Security::requireCsrf();

        $messageModel = new ContactMessage();
        if (!$messageModel->find((int) $id)) {
            $this->abort404();
        }

        $messageModel->setStatus((int) $id, 'replied');
        $this->logActivity('message_marked_replied', "Marked contact message #$id as replied");

        flash('success', 'Marked as replied.');
        redirect('admin/messages/' . $id);
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $messageModel = new ContactMessage();
        if (!$messageModel->find((int) $id)) {
            $this->abort404();
        }

        $messageModel->delete((int) $id);
        $this->logActivity('message_deleted', "Deleted contact message #$id");

        flash('success', 'Message deleted.');
        redirect('admin/messages');
    }
}
