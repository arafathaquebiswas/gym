<?php $pageTitle = 'Register'; ?>

<section class="section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">
        <div class="glass-card p-4 p-md-5">
          <h3 class="text-center mb-1">Create Your Account</h3>
          <p class="text-white-50 text-center mb-4">Join PowerSurge Gym — activate a membership package in person after signing up.</p>
          <form method="post" action="<?= url('/register') ?>" class="form-ps needs-validation" novalidate>
            <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">
            <div class="mb-3">
              <label>Full Name</label>
              <input type="text" name="name" class="form-control" value="<?= old('name') ?>" required>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= old('phone') ?>" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
              </div>
              <div class="col-md-6 mb-3">
                <label>Confirm Password</label>
                <input type="password" name="password_confirm" class="form-control" minlength="8" required>
              </div>
            </div>
            <div class="form-check mb-4">
              <input class="form-check-input" type="checkbox" id="terms" required>
              <label class="form-check-label text-white-50" for="terms">I agree to the gym's terms and membership policy.</label>
            </div>
            <button type="submit" class="btn btn-ps w-100">Create Account</button>
          </form>
          <p class="text-center text-white-50 mt-4 mb-0">Already a member? <a href="<?= url('/login') ?>">Log in</a></p>
        </div>
      </div>
    </div>
  </div>
</section>
