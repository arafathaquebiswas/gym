<?php

/**
 * Last line of defence for uncaught exceptions and fatal errors.
 *
 * Without this, a single broken page gives the visitor either a raw stack trace
 * (which leaks absolute server paths) or a blank white screen in production,
 * while the request still returns HTTP 200 so search engines index the nothing.
 * Here it becomes: logged with full detail, HTTP 500, friendly page.
 */
final class ErrorHandler
{
    /** Fatals that shutdown has to pick up, because nothing else can catch them. */
    private const FATAL = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    private static bool $handled = false;

    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e): void
    {
        self::log(sprintf(
            'Uncaught %s: %s in %s:%d%s%s',
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            PHP_EOL,
            $e->getTraceAsString()
        ));

        self::render($e->getMessage(), $e->getFile(), $e->getLine());
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], self::FATAL, true)) {
            return;
        }

        self::log("Fatal error: {$error['message']} in {$error['file']}:{$error['line']}");
        self::render($error['message'], $error['file'], $error['line']);
    }

    /**
     * Always logged, regardless of APP_ENV — error_reporting(0) silences the
     * display of errors in production, not our own bookkeeping.
     */
    private static function log(string $message): void
    {
        error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . ' | URI: ' . ($_SERVER['REQUEST_URI'] ?? 'cli'));
    }

    private static function render(string $message, string $file, int $line): void
    {
        if (self::$handled || PHP_SAPI === 'cli') {
            return;
        }
        self::$handled = true;

        // A half-rendered view may already be buffered; drop it so the error page
        // is not appended to a broken page fragment.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        $detail = APP_DEBUG ? sprintf('%s in %s:%d', $message, $file, $line) : null;

        if (self::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Something went wrong on our end.'] + ($detail ? ['detail' => $detail] : []));
            return;
        }

        // The styled page needs a database (the layout loads settings and the
        // navbar), which is exactly what may have just failed — so a failure
        // rendering it falls through to markup that needs nothing at all.
        try {
            (new ErrorController())->serverError($detail);
            return;
        } catch (Throwable) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        echo self::fallbackHtml($detail);
    }

    private static function wantsJson(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

        return str_starts_with($path, '/api/')
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private static function fallbackHtml(?string $detail): string
    {
        $extra = $detail === null ? '' : '<pre style="text-align:left;white-space:pre-wrap;color:#f88">'
            . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</pre>';

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Something went wrong</title></head>'
            . '<body style="background:#111;color:#eee;font-family:system-ui,sans-serif;text-align:center;padding:4rem 1rem">'
            . '<h1>Something went wrong</h1>'
            . '<p>We hit an unexpected error. Please try again in a moment.</p>'
            . '<p><a href="/" style="color:#ff6b00">Back to Home</a></p>'
            . $extra
            . '</body></html>';
    }
}
