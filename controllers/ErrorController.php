<?php

final class ErrorController extends Controller
{
    public function notFound(): void
    {
        $this->view('errors/404');
    }
}
