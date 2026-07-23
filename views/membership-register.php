<?php
/** @var array $packages */
/** @var array $trainers */
$pageTitle = 'Register for Membership';
?>

<section class="section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-9 col-lg-8">
        <div class="glass-card p-4 p-md-5">
          <h3 class="text-center mb-1">Online Membership Registration</h3>
          <p class="text-white-50 text-center mb-4">No account or password needed — submit your details below, then visit or contact the PowerSurge Gym office to complete your payment and activate your membership.</p>
          <form method="post" action="<?= url('/register') ?>" class="form-ps needs-validation" novalidate>
            <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">

            <h6 class="text-white-50 text-uppercase small mb-3">Personal Information</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Full Name *</label>
                <input type="text" name="name" class="form-control" value="<?= old('name') ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label>Phone Number *</label>
                <input type="text" name="phone" class="form-control" value="<?= old('phone') ?>" required>
              </div>
              <div class="col-md-6 mb-3">
                <label>Email <small class="text-white-50">(optional)</small></label>
                <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label>Gender</label>
                <select name="gender" class="form-select">
                  <option value="">—</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label>Date of Birth</label>
                <input type="date" name="dob" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label>Emergency Contact</label>
                <input type="text" name="emergency_contact" class="form-control">
              </div>
              <div class="col-12 mb-3">
                <label>Address</label>
                <input type="text" name="address" class="form-control">
              </div>
            </div>

            <h6 class="text-white-50 text-uppercase small mb-3 mt-2">Membership Interest</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Preferred Package *</label>
                <select name="preferred_package_id" class="form-select" required>
                  <option value="" disabled selected>Select a package</option>
                  <?php foreach ($packages as $package): ?>
                    <option value="<?= (int) $package['id'] ?>"><?= e($package['name']) ?> (৳<?= number_format((float) $package['display_price']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if (!empty($trainers)): ?>
              <div class="col-md-6 mb-3">
                <label>Preferred Trainer <small class="text-white-50">(optional)</small></label>
                <select name="trainer_id" class="form-select">
                  <option value="">— No preference —</option>
                  <?php foreach ($trainers as $trainer): ?>
                    <option value="<?= (int) $trainer['id'] ?>"><?= e($trainer['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="col-12 mb-3">
                <label>Notes <small class="text-white-50">(optional)</small></label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Anything you'd like the gym to know before your visit"></textarea>
              </div>
            </div>

            <h6 class="text-white-50 text-uppercase small mb-3 mt-2">Already Paid Online? <small class="text-white-50 text-normal text-lowercase">(optional)</small></h6>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="alreadyPaid" onchange="document.getElementById('paidFields').classList.toggle('d-none', !this.checked)">
              <label class="form-check-label text-white-50" for="alreadyPaid">I've already sent a payment (e.g. via bKash/Nagad/Rocket/bank) for this membership</label>
            </div>
            <div id="paidFields" class="row d-none">
              <p class="text-white-50 small col-12">This just helps our staff find and verify your payment faster — it doesn't activate your membership automatically. We'll still confirm it with you at the office.</p>
              <div class="col-md-6 mb-3">
                <label>Payment Method</label>
                <select name="reported_payment_method" id="reportedMethod" class="form-select">
                  <option value="">— Select —</option>
                  <option value="bkash">bKash</option>
                  <option value="nagad">Nagad</option>
                  <option value="rocket">Rocket</option>
                  <option value="card">Card</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>
              <div class="col-md-6 mb-3 d-none" id="reportedPayerWrap">
                <label>bKash/Nagad/Rocket Number Used</label>
                <input type="text" name="reported_payer_number" id="reportedPayerNumber" class="form-control" placeholder="e.g. 017XXXXXXXX">
              </div>
              <div class="col-md-6 mb-3">
                <label>Transaction ID / Reference Number</label>
                <input type="text" name="reported_payment_reference" class="form-control" placeholder="e.g. bKash TrxID or bank reference">
              </div>
              <p class="text-white-50 small col-12 mb-0">For bKash, Nagad, or Rocket, please provide both the sender number and the Transaction ID — our staff need both to find and verify your payment.</p>
            </div>
            <script>
            (function () {
              var method = document.getElementById('reportedMethod');
              var payerWrap = document.getElementById('reportedPayerWrap');
              var payerInput = document.getElementById('reportedPayerNumber');
              if (!method || !payerWrap || !payerInput) return;
              var MFS = ['bkash', 'nagad', 'rocket'];
              method.addEventListener('change', function () {
                var isMfs = MFS.indexOf(method.value) !== -1;
                payerWrap.classList.toggle('d-none', !isMfs);
                payerInput.required = isMfs;
              });
            })();
            </script>

            <p class="text-white-50 small text-center mt-3 mb-0">Prefer to pay in person? You don't need to fill in the section above — simply visit the PowerSurge Gym office and pay there once you arrive.</p>

            <button type="submit" class="btn btn-ps w-100 mt-3">Submit Registration</button>
          </form>
          <p class="text-center text-white-50 mt-4 mb-0">Already registered? Visit or contact the PowerSurge Gym office to complete your payment.</p>
        </div>
      </div>
    </div>
  </div>
</section>
