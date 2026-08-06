<?php
/** @var array $packages */
/** @var array $trainers */
$pageTitle = 'Register for Membership';

// Server-side messages from a rejected submit, shown against the field that caused
// them. field_error() is already escaped and cached, so calling it per field is safe.
$errClass = static fn (string $field): string => field_error($field) !== '' ? ' is-invalid' : '';
$errMsg = static function (string $field): string {
    $message = field_error($field);
    return $message === '' ? '' : '<div class="invalid-feedback d-block">' . $message . '</div>';
};
?>

<style>
  .required-star { color: #ff5a5a; }
  .optional-hint { font-weight: 400; }
</style>

<section class="section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-9 col-lg-8">
        <div class="glass-card p-4 p-md-5">
          <div class="text-center mb-4">
            <h3 class="mb-1">Online Membership Registration</h3>
            <p class="text-white-50 mb-0">No account or password needed — submit your details below, then visit or contact the POWERSURGE GYM & NUTRITION office to complete your payment and activate your membership.</p>
          </div>
          <form method="post" action="<?= url('/register') ?>" class="form-ps needs-validation" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">

            <p class="text-white-50 small mb-3">Fields marked <span class="required-star">*</span> are required. Everything else is optional.</p>

            <h6 class="text-white-50 text-uppercase small mb-3">Personal Information</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="regName">Full Name <span class="required-star">*</span></label>
                <input type="text" id="regName" name="name" class="form-control<?= $errClass('name') ?>" value="<?= old('name') ?>" required>
                <?= $errMsg('name') ?>
              </div>
              <div class="col-md-6 mb-3">
                <label for="regPhone">Phone Number <span class="required-star">*</span></label>
                <input type="tel" id="regPhone" name="phone" class="form-control<?= $errClass('phone') ?>" value="<?= old('phone') ?>" inputmode="tel" placeholder="e.g. 017XXXXXXXX" required>
                <?= $errMsg('phone') ?>
              </div>
              <div class="col-md-6 mb-3">
                <label for="regEmergency">Emergency Contact Number <span class="required-star">*</span></label>
                <input type="tel" id="regEmergency" name="emergency_contact" class="form-control<?= $errClass('emergency_contact') ?>" value="<?= old('emergency_contact') ?>" inputmode="tel" placeholder="Someone we can reach in an emergency" required>
                <?= $errMsg('emergency_contact') ?>
              </div>
              <div class="col-md-6 mb-3">
                <label for="regEmail">Email <small class="text-white-50 optional-hint">(optional)</small></label>
                <input type="email" id="regEmail" name="email" class="form-control<?= $errClass('email') ?>" value="<?= old('email') ?>">
                <?= $errMsg('email') ?>
              </div>
              <div class="col-12 mb-3">
                <label for="regAddress">Address <span class="required-star">*</span></label>
                <input type="text" id="regAddress" name="address" class="form-control<?= $errClass('address') ?>" value="<?= old('address') ?>" placeholder="House / road / area, city" required>
                <?= $errMsg('address') ?>
              </div>
              <div class="col-md-6 mb-3">
                <label for="regGender">Gender <small class="text-white-50 optional-hint">(optional)</small></label>
                <select name="gender" id="regGender" class="form-select">
                  <?php foreach (['' => '—', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= old('gender') === $value && $value !== '' ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label for="regDob">Date of Birth <small class="text-white-50 optional-hint">(optional)</small></label>
                <input type="date" id="regDob" name="dob" class="form-control" value="<?= old('dob') ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="regCurrentWeight">Current Weight (kg) <small class="text-white-50 optional-hint">(optional)</small></label>
                <input type="number" id="regCurrentWeight" name="current_weight" class="form-control<?= $errClass('current_weight') ?>" value="<?= old('current_weight') ?>" step="0.1" min="1" max="500" inputmode="decimal" placeholder="e.g. 78.5">
                <?= $errMsg('current_weight') ?>
              </div>
              <div class="col-md-6 mb-3">
                <label for="regTargetWeight">Target Weight (kg) <small class="text-white-50 optional-hint">(optional)</small></label>
                <input type="number" id="regTargetWeight" name="target_weight" class="form-control<?= $errClass('target_weight') ?>" value="<?= old('target_weight') ?>" step="0.1" min="1" max="500" inputmode="decimal" placeholder="e.g. 72">
                <?= $errMsg('target_weight') ?>
              </div>
              <div class="col-12 mb-3">
                <label for="regPhoto">Profile Picture <small class="text-white-50 optional-hint">(optional)</small></label>
                <input type="file" id="regPhoto" name="photo" class="form-control<?= $errClass('photo') ?>" accept="image/jpeg,image/png,image/webp">
                <div class="form-text text-white-50">JPG, PNG or WebP, up to <?= round(MAX_UPLOAD_SIZE / 1024 / 1024, 1) ?>MB. Leave this empty and we'll use the POWERSURGE logo until you bring a photo to the office.</div>
                <?= $errMsg('photo') ?>
              </div>
            </div>

            <h6 class="text-white-50 text-uppercase small mb-3 mt-2">Membership Interest</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="regPackage">Preferred Package <span class="required-star">*</span></label>
                <select name="preferred_package_id" id="regPackage" class="form-select<?= $errClass('preferred_package_id') ?>" required>
                  <option value="" disabled <?= old('preferred_package_id') === '' ? 'selected' : '' ?>>Select a package</option>
                  <?php foreach ($packages as $package): ?>
                    <option value="<?= (int) $package['id'] ?>" <?= old('preferred_package_id') === (string) $package['id'] ? 'selected' : '' ?>><?= e($package['name']) ?> (৳<?= number_format((float) $package['display_price']) ?>)</option>
                  <?php endforeach; ?>
                </select>
                <?= $errMsg('preferred_package_id') ?>
              </div>
              <?php if (!empty($trainers)): ?>
              <div class="col-md-6 mb-3">
                <label for="regTrainer">Preferred Trainer <small class="text-white-50 optional-hint">(optional)</small></label>
                <select name="trainer_id" id="regTrainer" class="form-select">
                  <option value="">— No preference —</option>
                  <?php foreach ($trainers as $trainer): ?>
                    <option value="<?= (int) $trainer['id'] ?>" <?= old('trainer_id') === (string) $trainer['id'] ? 'selected' : '' ?>><?= e($trainer['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="col-md-6 mb-3">
                <label for="regCoupon">Coupon Code <small class="text-white-50 optional-hint">(optional)</small></label>
                <input type="text" id="regCoupon" name="coupon_code" class="form-control text-uppercase<?= $errClass('coupon_code') ?>" value="<?= old('coupon_code') ?>" autocapitalize="characters" autocomplete="off" placeholder="Have one? Enter it here">
                <div class="form-text text-white-50">Leave blank if you don't have a coupon — it won't affect your registration.</div>
                <?= $errMsg('coupon_code') ?>
              </div>
              <div class="col-12 mb-3">
                <label for="regNotes">Notes <small class="text-white-50 optional-hint">(optional)</small></label>
                <textarea name="notes" id="regNotes" class="form-control" rows="2" placeholder="Anything you'd like the gym to know before your visit"><?= old('notes') ?></textarea>
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

            <p class="text-white-50 small text-center mt-3 mb-0">Prefer to pay in person? You don't need to fill in the section above — simply visit the POWERSURGE GYM & NUTRITION office and pay there once you arrive.</p>

            <button type="submit" class="btn btn-ps w-100 mt-3">Submit Registration</button>
          </form>
          <p class="text-center text-white-50 mt-4 mb-0">Already registered? Visit or contact the POWERSURGE GYM & NUTRITION office to complete your payment.</p>
        </div>
      </div>
    </div>
  </div>
</section>
