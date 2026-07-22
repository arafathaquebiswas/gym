<?php

final class ContactController extends Controller
{
    public function index(): void
    {
        $settingModel = new Setting();
        $this->view('contact', ['settings' => $settingModel->all()]);
    }
}
