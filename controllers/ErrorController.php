<?php

final class ErrorController extends Controller
{
    public function notFound(): void
    {
        $this->view('errors/404');
    }

    /** @param string|null $detail Error text — passed through only when APP_DEBUG is on. */
    public function serverError(?string $detail = null): void
    {
        $this->view('errors/500', ['detail' => $detail]);
    }
}
