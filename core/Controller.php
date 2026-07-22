<?php

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $flashes = get_flashes();
        $viewFile = BASE_PATH . '/views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: $view");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require BASE_PATH . '/views/layouts/main.php';
    }

    protected function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/views/' . $view . '.php';
    }

    protected function adminView(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $flashes = get_flashes();
        $viewFile = BASE_PATH . '/views/admin/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("Admin view not found: $view");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require BASE_PATH . '/views/layouts/admin.php';
    }

    protected function input(string $key, string $default = ''): string
    {
        return Security::sanitizeString($_POST[$key] ?? $_GET[$key] ?? $default);
    }

    protected function rawInput(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $_GET[$key] ?? $default));
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function abort404(): never
    {
        http_response_code(404);
        $this->view('errors/404');
        exit;
    }
}
