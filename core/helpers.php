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

/**
 * Assets carry a ?v= stamp of their last-modified time, so a CSS/JS edit reaches browsers
 * on the next request instead of sitting behind a cached copy until a hard refresh.
 */
function asset(string $path): string
{
    $relative = 'assets/' . ltrim($path, '/');
    $file = BASE_PATH . '/' . $relative;
    $version = is_file($file) ? filemtime($file) : false;

    return url($relative) . ($version ? '?v=' . $version : '');
}

function upload_url(string $path): string
{
    return url('uploads/' . ltrim($path, '/'));
}

/**
 * What the visitor typed on a submit that was rejected, so the form can be
 * re-rendered without losing their work. Read once and then dropped — left in
 * the session it would keep repopulating the form on every later visit.
 */
function old(string $key, string $default = ''): string
{
    static $values = null;
    if ($values === null) {
        $values = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }

    return e((string) ($values[$key] ?? $default));
}

/** The validation message for one field from a rejected submit, or ''. Consumed like old(). */
function field_error(string $key): string
{
    static $errors = null;
    if ($errors === null) {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
    }

    return e((string) ($errors[$key] ?? ''));
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
    $addr = trim(($order['delivery_address'] ?? '') . ', ' . ($order['delivery_city'] ?? ''), ', ');
    return $addr !== '' ? $addr : 'N/A';
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
    return '<div class="media-tile-fallback d-flex flex-column align-items-center justify-content-center h-100 text-center w-100 p-2" style="background:#f1f5f9; color:#94a3b8; border-radius:8px;"><i class="bi bi-camera-fill mb-1" style="font-size:1.8rem; color:#cbd5e1;"></i><span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#64748b;">No Image Available</span></div>';
}
