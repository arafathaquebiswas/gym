<?php

final class FreeTrialController extends Controller
{
    public function register(): void
    {
        Security::requireCsrf();

        $settingModel = new Setting();
        if (!$settingModel->getBool('free_trial_enabled')) {
            flash('danger', 'Free trial registration is not currently available.');
            redirect('');
        }

        $today = date('Y-m-d');
        $startDate = $settingModel->get('free_trial_start_date');
        $endDate = $settingModel->get('free_trial_end_date');
        if (($startDate && $today < $startDate) || ($endDate && $today > $endDate)) {
            flash('danger', 'The free trial offer is not currently active.');
            redirect('');
        }

        $registrationModel = new FreeTrialRegistration();
        $max = $settingModel->getInt('free_trial_max_registrations', 0);
        if ($max > 0 && $registrationModel->count() >= $max) {
            flash('danger', 'Sorry, the free trial has reached its maximum number of registrations.');
            redirect('');
        }

        $name = $this->input('name');
        $phone = $this->input('phone');
        $email = $this->input('email') ?: null;

        $validator = new Validator(['name' => $name, 'phone' => $phone]);
        $validator->required('name', 'Name')->required('phone', 'Phone')->phone('phone');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('');
        }

        $registrationModel->create($name, $phone, $email);

        flash('success', 'Thanks! We\'ll contact you shortly to schedule your free trial session.');
        redirect('');
    }
}
