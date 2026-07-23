<?php
/**
 * Small global helpers used across views/controllers.
 */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function upload_url(string $path): string
{
    return url('uploads/' . ltrim($path, '/'));
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function money(float $amount): string
{
    $symbol = (new Setting())->get('currency_symbol', 'BDT');
    return $symbol . ' ' . number_format($amount, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * The customer's delivery address, or the gym's own address when the order is a
 * store pickup (fulfillment_method='pickup', so delivery_address/city are null).
 * Returns raw (unescaped) text — HTML call sites should wrap with e().
 */
function order_delivery_label(array $order): string
{
    if (($order['fulfillment_method'] ?? 'delivery') === 'pickup') {
        $settingModel = new Setting();
        $gymName = $settingModel->get('gym_name', 'the gym');
        $gymAddress = $settingModel->get('gym_address', '');
        return $gymName . ($gymAddress ? ' — ' . $gymAddress : '');
    }
    return $order['delivery_address'] . ', ' . $order['delivery_city'];
}

/**
 * Renders a photo when available, falling back to a supplied placeholder
 * image (e.g. a default avatar) or, failing that, the dashed placeholder
 * tile used before any photo exists for that record.
 *
 * $relativePath is either relative to /assets/images (seeded/bundled site
 * photos) or a full "uploads/..." web path (admin-uploaded via Upload::handle) —
 * the "uploads/" prefix on the stored value is what tells them apart, so
 * callers never need to know which source a given record's photo came from.
 */
function media_tile(?string $relativePath, string $alt, string $iconClass = 'bi-image', string $class = '', ?string $fallbackImage = null): string
{
    if ($relativePath) {
        $src = str_starts_with($relativePath, 'uploads/') ? url($relativePath) : asset('images/' . $relativePath);
        return '<img src="' . e($src) . '" alt="' . e($alt) . '" class="photo-tile ' . e($class) . '" loading="lazy">';
    }
    if ($fallbackImage) {
        return '<img src="' . e($fallbackImage) . '" alt="' . e($alt) . '" class="photo-tile ' . e($class) . '" loading="lazy">';
    }
    return '<div class="img-placeholder h-100"><i class="bi ' . e($iconClass) . '"></i></div>';
}
