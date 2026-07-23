<?php
/**
 * AJAX (and progressively-enhanced non-JS fallback) contact form endpoint.
 */

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

Security::requireCsrf();

if (!Feature::on('contact_form')) {
    if (Security::wantsJson()) {
        json_response(['success' => false, 'message' => 'The contact form is not currently available.'], 404);
    }
    flash('danger', 'The contact form is not currently available.');
    redirect('contact');
}

$name = Security::sanitizeString($_POST['name'] ?? '');
$email = Security::sanitizeString($_POST['email'] ?? '');
$phone = Security::sanitizeString($_POST['phone'] ?? '');
$subject = Security::sanitizeString($_POST['subject'] ?? '');
$message = Security::sanitizeString($_POST['message'] ?? '');

$validator = new Validator(['name' => $name, 'email' => $email, 'message' => $message]);
$validator->required('name', 'Name')
    ->required('email', 'Email')
    ->email('email')
    ->required('message', 'Message');

$respond = function (bool $success, string $message) {
    if (Security::wantsJson()) {
        json_response(['success' => $success, 'message' => $message], $success ? 200 : 422);
    }
    flash($success ? 'success' : 'danger', $message);
    redirect('contact');
};

if ($validator->fails()) {
    $respond(false, $validator->firstError());
}

$contactModel = new ContactMessage();
$contactModel->create($name, $email, $phone, $subject, $message);

$settingModel = new Setting();
$gymEmail = $settingModel->get('gym_email');
if ($gymEmail) {
    Mailer::send(
        $gymEmail,
        'PowerSurge Gym',
        'New Contact Message: ' . ($subject ?: 'Website Inquiry'),
        '<p><strong>From:</strong> ' . e($name) . ' (' . e($email) . ')</p><p><strong>Phone:</strong> ' . e($phone) . '</p><p>' . nl2br(e($message)) . '</p>'
    );
}

$respond(true, 'Thanks for reaching out! We will get back to you shortly.');
