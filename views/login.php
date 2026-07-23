<?php $pageTitle = 'Login'; ?>

<section class="section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="glass-card p-4 p-md-5">
          <h3 class="text-center mb-1">Welcome Back</h3>
          <p class="text-white-50 text-center mb-4">Log in to your PowerSurge account.</p>
          <form method="post" action="<?= url('/login') ?>" class="form-ps needs-validation" novalidate>
            <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">
            <div class="mb-3">
              <label>Email or ID</label>
              <input type="text" name="email" class="form-control" value="<?= old('email') ?>" required>
            </div>
            <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label text-white-50" for="remember">Remember Me</label>
              </div>
              <a href="<?= url('/contact') ?>" class="small">Forgot Password?</a>
            </div>
            <button type="submit" class="btn btn-ps w-100">Log In</button>
          </form>
          <p class="text-center text-white-50 mt-4 mb-0">New here? <a href="<?= url('/register') ?>">Create an account</a></p>
        </div>
      </div>
    </div>
  </div>
</section>
