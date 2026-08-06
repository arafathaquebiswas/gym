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

            <h6 class="text-white-50 text-uppercase small mb-3 mt-2">Payment</h6>
            <?php $payOnline = old('payment_mode') === 'online'; ?>
            <div class="row">
              <div class="col-md-6 mb-3">
                <div class="form-check p-3 border rounded h-100" style="border-color:rgba(255,255,255,.15)!important">
                  <input class="form-check-input" type="radio" name="payment_mode" value="gym" id="payAtGym" <?= $payOnline ? '' : 'checked' ?>>
                  <label class="form-check-label" for="payAtGym">
                    <strong>Pay at Gym</strong>
                    <span class="d-block text-white-50 small">Register now, pay when you visit the office.</span>
                  </label>
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="form-check p-3 border rounded h-100" style="border-color:rgba(255,255,255,.15)!important">
                  <input class="form-check-input" type="radio" name="payment_mode" value="online" id="payOnline" <?= $payOnline ? 'checked' : '' ?>>
                  <label class="form-check-label" for="payOnline">
                    <strong>Online Payment</strong>
                    <span class="d-block text-white-50 small">Pay now with bKash or Nagad and upload your receipt.</span>
                  </label>
                </div>
              </div>
            </div>

            <div id="onlinePaymentFields" class="<?= $payOnline ? '' : 'd-none' ?>">
              <div class="row align-items-center g-3 mb-3 p-3 rounded" style="background:rgba(255,255,255,.04)">
                <div class="col-sm-auto text-center">
                  <?php $qr = BASE_PATH . '/assets/images/payment/bkash-qr.png'; ?>
                  <?php if (is_file($qr)): ?>
                    <img src="<?= asset('images/payment/bkash-qr.png') ?>" alt="bKash QR code for POWERSURGE GYM & NUTRITION" class="img-fluid rounded bg-white p-2" style="max-width:190px">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center rounded bg-white text-dark text-center p-3" style="width:190px;height:190px">
                      <span class="small">QR code image not uploaded yet — please pay to the merchant number below.</span>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="col-sm">
                  <p class="mb-2">Merchant number: <strong class="fs-5"><?= e(PAYMENT_MERCHANT_NUMBER) ?></strong></p>
                  <p class="small mb-2" style="color:#ff6b6b"><strong>Use "Payment" only — "Send Money" is not accepted.</strong> <span class="text-white-50">শুধু মাত্র Payment করুন, Send Money প্রযোজ্য নয়।</span></p>
                  <p class="text-white-50 small mb-2">Please make the payment using the QR code above. After completing the payment, enter your Transaction ID (TrxID) and upload a screenshot of the successful payment.</p>
                  <p class="text-white-50 small mb-2">A 1.3% charge applies to bKash and Nagad payments. <span class="text-white-50">বিকাশ বা নগদ পেমেন্ট এর জন্য ১.৩% চার্জ প্রযোজ্য।</span></p>
                  <p class="small mb-0" style="color:#ffc107">⚠️ Only complete (successful) payment screenshots are accepted. Membership will be activated only after admin verification.</p>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="regPaymentMethod">Payment Method <span class="required-star">*</span></label>
                  <select name="payment_method" id="regPaymentMethod" class="form-select<?= $errClass('payment_method') ?>">
                    <option value="">— Select —</option>
                    <?php foreach (['bkash' => 'bKash', 'nagad' => 'Nagad'] as $value => $label): ?>
                      <option value="<?= $value ?>" <?= old('payment_method') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?= $errMsg('payment_method') ?>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="regPaymentType">Payment Type <span class="required-star">*</span></label>
                  <select name="payment_type" id="regPaymentType" class="form-select<?= $errClass('payment_type') ?>">
                    <option value="">— Select —</option>
                    <?php foreach (['qr' => 'QR Payment', 'mobile' => 'Mobile Number Payment'] as $value => $label): ?>
                      <option value="<?= $value ?>" <?= old('payment_type') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?= $errMsg('payment_type') ?>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="regTrxId">Transaction ID (TrxID) <span class="required-star">*</span></label>
                  <input type="text" id="regTrxId" name="transaction_id" class="form-control text-uppercase<?= $errClass('transaction_id') ?>" value="<?= old('transaction_id') ?>" autocapitalize="characters" autocomplete="off" placeholder="e.g. 9A7BCD123EF">
                  <?= $errMsg('transaction_id') ?>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="regScreenshot">Payment Screenshot <span class="required-star">*</span></label>
                  <input type="file" id="regScreenshot" name="payment_screenshot" class="form-control<?= $errClass('payment_screenshot') ?>" accept="image/jpeg,image/png,image/webp">
                  <div class="form-text text-white-50">JPG, JPEG, PNG or WebP, up to 5MB. You'll need to pick the file again if the form comes back with an error.</div>
                  <?= $errMsg('payment_screenshot') ?>
                </div>
              </div>
            </div>

            <p class="text-white-50 small text-center mt-3 mb-0">Prefer to pay in person? Choose <strong>Pay at Gym</strong> above — simply visit the POWERSURGE GYM &amp; NUTRITION office and pay there once you arrive.</p>

            <script>
            (function () {
              var fields = document.getElementById('onlinePaymentFields');
              var modes = document.querySelectorAll('input[name="payment_mode"]');
              if (!fields || !modes.length) return;

              // Required is toggled alongside visibility: a hidden required field blocks
              // submission with a validation bubble the visitor cannot see.
              var required = ['regPaymentMethod', 'regPaymentType', 'regTrxId', 'regScreenshot'];
              function sync() {
                var online = document.getElementById('payOnline').checked;
                fields.classList.toggle('d-none', !online);
                required.forEach(function (id) {
                  var el = document.getElementById(id);
                  if (el) { el.required = online; }
                });
              }
              modes.forEach(function (m) { m.addEventListener('change', sync); });
              sync();
            })();
            </script>

            <button type="submit" class="btn btn-ps w-100 mt-3">Submit Registration</button>
          </form>
          <p class="text-center text-white-50 mt-4 mb-0">Already registered? Visit or contact the POWERSURGE GYM & NUTRITION office to complete your payment.</p>
        </div>
      </div>
    </div>
  </div>
</section>
