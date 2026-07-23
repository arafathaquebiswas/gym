<?php
/** @var array $settings */
$v = fn ($key, $default = '') => e($settings[$key] ?? $default);
$yn = function (string $key, string $default = '1') use ($settings) {
    $val = $settings[$key] ?? $default;
    return '<option value="1" ' . ($val === '1' ? 'selected' : '') . '>Enabled</option>'
         . '<option value="0" ' . ($val === '0' ? 'selected' : '') . '>Disabled</option>';
};
?>
<ul class="nav nav-tabs mb-4" id="settingsTabs">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-gym">Gym Info</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-hours">Business Hours</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-currency">Currency &amp; Fines</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-shipping">Shipping &amp; Tax</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-delivery-pickup">Delivery &amp; Pickup</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-discounts">Discounts</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-membership">Membership</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-features">Feature Flags</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-automation">Automation</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-free-trial">Free Trial</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-smtp">SMTP</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-backup">Backup &amp; Restore</a></li>
</ul>

<form method="post" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" class="admin-form">
  <?= Security::csrfField() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="tab-gym">
      <div class="admin-card">
        <h6 class="mb-3">Gym Information</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label>Gym Name</label>
            <input type="text" name="gym_name" class="form-control" value="<?= $v('gym_name', 'PowerSurge Gym') ?>">
          </div>
          <div class="col-md-6">
            <label>Phone</label>
            <input type="text" name="gym_phone" class="form-control" value="<?= $v('gym_phone', '01904-485009') ?>">
          </div>
          <div class="col-md-6">
            <label>Email</label>
            <input type="email" name="gym_email" class="form-control" value="<?= $v('gym_email') ?>">
          </div>
          <div class="col-md-6">
            <label>Tagline</label>
            <input type="text" name="gym_tagline" class="form-control" value="<?= $v('gym_tagline', 'Train Hard. Surge Ahead.') ?>">
          </div>
          <div class="col-md-6">
            <label>Facebook URL</label>
            <input type="url" name="facebook_url" class="form-control" value="<?= $v('facebook_url') ?>">
          </div>
          <div class="col-md-6">
            <label>Instagram URL</label>
            <input type="url" name="instagram_url" class="form-control" value="<?= $v('instagram_url', 'https://instagram.com/powersurge_gym_01') ?>">
          </div>
          <div class="col-md-6">
            <label>WhatsApp Number</label>
            <input type="text" name="whatsapp_number" class="form-control" value="<?= $v('whatsapp_number', '+8801904485009') ?>">
          </div>
          <div class="col-md-6">
            <label>YouTube URL</label>
            <input type="url" name="youtube_url" class="form-control" value="<?= $v('youtube_url') ?>">
          </div>
          <div class="col-md-6">
            <label>TikTok URL</label>
            <input type="url" name="tiktok_url" class="form-control" value="<?= $v('tiktok_url') ?>">
          </div>
          <div class="col-12">
            <label>Address</label>
            <input type="text" name="gym_address" class="form-control" value="<?= $v('gym_address') ?>">
          </div>
          <div class="col-12">
            <label>Google Map Embed URL</label>
            <input type="url" name="google_map_embed" class="form-control" value="<?= $v('google_map_embed') ?>" placeholder="Google Maps embed iframe src URL">
          </div>
          <div class="col-md-6">
            <label>Logo</label>
            <?php if (!empty($settings['gym_logo'])): ?>
              <div class="mb-2"><?= media_tile($settings['gym_logo'], 'Gym Logo', 'bi-image', '', null) ?></div>
            <?php endif; ?>
            <input type="file" name="gym_logo" class="form-control" accept="image/jpeg,image/png,image/webp">
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-hours">
      <div class="admin-card">
        <h6 class="mb-3">Business Hours</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label>Saturday – Thursday</label>
            <input type="text" name="business_hours_weekday" class="form-control" value="<?= $v('business_hours_weekday', '7:00 AM – 11:00 PM') ?>">
          </div>
          <div class="col-md-6">
            <label>Friday</label>
            <input type="text" name="business_hours_friday" class="form-control" value="<?= $v('business_hours_friday', '5:00 PM – 10:00 PM') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-currency">
      <div class="admin-card">
        <h6 class="mb-3">Currency &amp; Fines</h6>
        <div class="row g-3">
          <div class="col-md-4">
            <label>Currency Symbol <small class="text-white-50">(prefixes every price shown on the site)</small></label>
            <input type="text" name="currency_symbol" class="form-control" value="<?= $v('currency_symbol', 'BDT') ?>">
          </div>
          <div class="col-md-4">
            <label>Late Payment Fine (per day)</label>
            <input type="number" step="0.01" min="0" name="late_fine_amount" class="form-control" value="<?= $v('late_fine_amount', '0') ?>">
          </div>
          <div class="col-md-4">
            <label>Lost Locker Key Fine</label>
            <input type="number" step="0.01" min="0" name="lost_locker_fine" class="form-control" value="<?= $v('lost_locker_fine', '0') ?>">
          </div>
          <div class="col-md-4">
            <label>Default Trainer Fee <small class="text-white-50">(monthly, suggested for new trainers)</small></label>
            <input type="number" step="0.01" min="0" name="default_trainer_fee" class="form-control" value="<?= $v('default_trainer_fee', '0') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-shipping">
      <div class="admin-card">
        <h6 class="mb-3">Shipping &amp; Tax</h6>
        <p class="text-white-50 small">Applies to online store orders (checkout) — not in-store POS sales.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <label>Shipping</label>
            <select name="shipping_enabled" class="form-select"><?= $yn('shipping_enabled') ?></select>
          </div>
          <div class="col-md-6">
            <label>Tax</label>
            <select name="tax_enabled" class="form-select"><?= $yn('tax_enabled') ?></select>
          </div>
          <div class="col-md-4">
            <label>Shipping Rate (৳)</label>
            <input type="number" step="0.01" min="0" name="shipping_flat_rate" class="form-control" value="<?= $v('shipping_flat_rate', '60') ?>">
          </div>
          <div class="col-md-4">
            <label>Minimum for Free Shipping (৳)</label>
            <input type="number" step="0.01" min="0" name="free_shipping_min_amount" class="form-control" value="<?= $v('free_shipping_min_amount', '2000') ?>">
          </div>
          <div class="col-md-4">
            <label>Tax Percentage (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="tax_percent" class="form-control" value="<?= $v('tax_percent', '0') ?>">
          </div>
          <div class="col-12">
            <label>Estimated Delivery Text <small class="text-white-50">(shown on order tracking page)</small></label>
            <input type="text" name="delivery_estimate_text" class="form-control" value="<?= $v('delivery_estimate_text', '3–5 business days') ?>">
          </div>
          <div class="col-md-6">
            <label>Apply Tax to Membership Payments</label>
            <select name="tax_applies_to_membership" class="form-select"><?= $yn('tax_applies_to_membership', '0') ?></select>
          </div>
          <div class="col-md-6">
            <label>Apply Tax to Trainer Fee Payments</label>
            <select name="tax_applies_to_trainer_fee" class="form-select"><?= $yn('tax_applies_to_trainer_fee', '0') ?></select>
          </div>
          <div class="col-12">
            <p class="text-white-50 small mb-0">Product-specific shipping can be set per-product on the product's edit page (overrides the flat rate above for that item).</p>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-delivery-pickup">
      <div class="admin-card">
        <h6 class="mb-3">Delivery &amp; Pickup</h6>
        <p class="text-white-50 small">If both are disabled, the store is hidden site-wide with a "temporarily unavailable" message — there would be no way to fulfill an order.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <label>Home Delivery</label>
            <select name="feature_delivery" class="form-select"><?= $yn('feature_delivery') ?></select>
          </div>
          <div class="col-md-6">
            <label>Store Pickup</label>
            <select name="feature_pickup" class="form-select"><?= $yn('feature_pickup') ?></select>
          </div>
          <div class="col-md-6">
            <label>Delivery Staff Earnings per Order (৳) <small class="text-white-50">(optional — leave 0 to hide earnings on their dashboard)</small></label>
            <input type="number" step="0.01" min="0" name="delivery_fee_per_order" class="form-control" value="<?= $v('delivery_fee_per_order', '0') ?>">
          </div>
        </div>
        <hr>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('/admin/delivery-zones') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-geo-alt"></i> Manage Delivery Zones</a>
          <a href="<?= url('/admin/delivery-time-slots') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-clock"></i> Manage Time Slots</a>
          <a href="<?= url('/admin/delivery-staff') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-person-badge"></i> Manage Delivery Staff</a>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-discounts">
      <div class="admin-card">
        <h6 class="mb-3">Discounts &amp; Promotions</h6>
        <p class="text-white-50 small">Priority when several offers could apply to the same item: Coupon &gt; Flash Sale &gt; Product Offer &gt; Category Offer &gt; Brand Offer &gt; Regular Price. Only the highest-priority one applies unless stacking is allowed below. Buy One Get One and Bundle savings are separate quantity-based mechanics and always apply on top.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <label>Allow Coupons to Stack with Item-Level Offers</label>
            <select name="discount_stacking_enabled" class="form-select"><?= $yn('discount_stacking_enabled', '0') ?></select>
          </div>
        </div>
        <hr>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('/admin/flash-sales') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-lightning-charge"></i> Manage Flash Sales</a>
          <a href="<?= url('/admin/bundles') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-box-seam"></i> Manage Bundles</a>
          <a href="<?= url('/admin/coupons') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-ticket-perforated"></i> Manage Coupons</a>
          <a href="<?= url('/admin/categories') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-tags"></i> Category Offers</a>
          <a href="<?= url('/admin/brands') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-award"></i> Brand Offers</a>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-membership">
      <div class="admin-card">
        <h6 class="mb-3">Membership Defaults</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label>Renewal Grace Period (days)</label>
            <input type="number" min="0" name="membership_grace_days" class="form-control" value="<?= $v('membership_grace_days', '3') ?>">
          </div>
          <div class="col-md-6">
            <label>Auto-Expire Overdue Memberships</label>
            <select name="auto_expire_memberships" class="form-select">
              <option value="1" <?= ($settings['auto_expire_memberships'] ?? '1') === '1' ? 'selected' : '' ?>>Yes</option>
              <option value="0" <?= ($settings['auto_expire_memberships'] ?? '1') === '0' ? 'selected' : '' ?>>No</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-features">
      <div class="admin-card">
        <h6 class="mb-3">Feature Flags</h6>
        <p class="text-white-50 small">Turn whole modules on or off without touching any code. Disabling a module hides it everywhere on the public site (and, where noted, in the admin panel) — existing data is never deleted.</p>
        <div class="row g-3">
          <div class="col-md-4">
            <label>Trainer Module <small class="text-white-50 d-block">Master switch — turns off booking, fee, display, and admin trainer management together</small></label>
            <select name="feature_trainer_module" class="form-select"><?= $yn('feature_trainer_module') ?></select>
          </div>
          <div class="col-md-4">
            <label>Trainer Fee</label>
            <select name="feature_trainer_fee" class="form-select"><?= $yn('feature_trainer_fee') ?></select>
          </div>
          <div class="col-md-4">
            <label>Trainer Booking</label>
            <select name="feature_trainer_booking" class="form-select"><?= $yn('feature_trainer_booking') ?></select>
          </div>
          <div class="col-md-4">
            <label>Show Trainers Section on Homepage</label>
            <select name="feature_trainer_display" class="form-select"><?= $yn('feature_trainer_display') ?></select>
          </div>
          <div class="col-md-4">
            <label>Membership Sales</label>
            <select name="feature_membership_sales" class="form-select"><?= $yn('feature_membership_sales') ?></select>
          </div>
          <div class="col-md-4">
            <label>Store</label>
            <select name="feature_store" class="form-select"><?= $yn('feature_store') ?></select>
          </div>
          <div class="col-md-4">
            <label>Offers Section (Homepage)</label>
            <select name="feature_offers" class="form-select"><?= $yn('feature_offers') ?></select>
          </div>
          <div class="col-md-4">
            <label>Coupons</label>
            <select name="feature_coupons" class="form-select"><?= $yn('feature_coupons') ?></select>
          </div>
          <div class="col-md-4">
            <label>Product Reviews</label>
            <select name="feature_reviews" class="form-select"><?= $yn('feature_reviews') ?></select>
          </div>
          <div class="col-md-4">
            <label>Guest Checkout</label>
            <select name="feature_guest_checkout" class="form-select"><?= $yn('feature_guest_checkout') ?></select>
          </div>
          <div class="col-md-4">
            <label>Pre-Order</label>
            <select name="feature_preorder" class="form-select"><?= $yn('feature_preorder') ?></select>
          </div>
          <div class="col-md-4">
            <label>Wishlist</label>
            <select name="feature_wishlist" class="form-select"><?= $yn('feature_wishlist') ?></select>
          </div>
          <div class="col-md-4">
            <label>Blog</label>
            <select name="feature_blog" class="form-select"><?= $yn('feature_blog') ?></select>
          </div>
          <div class="col-md-4">
            <label>Gallery</label>
            <select name="feature_gallery" class="form-select"><?= $yn('feature_gallery') ?></select>
          </div>
          <div class="col-md-4">
            <label>Contact Form</label>
            <select name="feature_contact_form" class="form-select"><?= $yn('feature_contact_form') ?></select>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-automation">
      <div class="admin-card">
        <h6 class="mb-3">Automation</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label>Auto Email Notifications <small class="text-white-50 d-block">Order confirmation emails and "back in stock" alerts to customers</small></label>
            <select name="auto_email_notifications" class="form-select"><?= $yn('auto_email_notifications') ?></select>
          </div>
          <div class="col-md-6">
            <label>Auto Low Stock Alerts <small class="text-white-50 d-block">Emails the gym's own email (below) the moment a product's stock drops to or below its minimum level</small></label>
            <select name="auto_low_stock_alerts" class="form-select"><?= $yn('auto_low_stock_alerts', '0') ?></select>
          </div>
          <div class="col-12">
            <p class="text-white-50 small mb-0">Low stock alerts are sent to the Gym Email set in the "Gym Info" tab, and require SMTP to be configured in the "SMTP" tab. Only fires once per drop below the threshold — not on every sale while stock stays low.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-free-trial">
      <div class="admin-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Free Trial Section</h6>
          <a href="<?= url('/admin/settings/free-trial-registrations') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-people"></i> View Registrations</a>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label>Free Trial Section</label>
            <select name="free_trial_enabled" class="form-select"><?= $yn('free_trial_enabled') ?></select>
          </div>
          <div class="col-md-4">
            <label>Start Date <small class="text-white-50">(optional)</small></label>
            <input type="date" name="free_trial_start_date" class="form-control" value="<?= $v('free_trial_start_date') ?>">
          </div>
          <div class="col-md-4">
            <label>End Date <small class="text-white-50">(optional)</small></label>
            <input type="date" name="free_trial_end_date" class="form-control" value="<?= $v('free_trial_end_date') ?>">
          </div>
          <div class="col-md-6">
            <label>Title</label>
            <input type="text" name="free_trial_title" class="form-control" value="<?= $v('free_trial_title', 'Free Trial Session') ?>">
          </div>
          <div class="col-md-6">
            <label>Subtitle</label>
            <input type="text" name="free_trial_subtitle" class="form-control" value="<?= $v('free_trial_subtitle', 'Free trial session this week — no commitment required.') ?>">
          </div>
          <div class="col-md-6">
            <label>Button Text</label>
            <input type="text" name="free_trial_button_text" class="form-control" value="<?= $v('free_trial_button_text', 'Claim Your Free Session') ?>">
          </div>
          <div class="col-md-6">
            <label>Button Link <small class="text-white-50">(leave blank to use the built-in registration form)</small></label>
            <input type="text" name="free_trial_button_link" class="form-control" value="<?= $v('free_trial_button_link') ?>" placeholder="e.g. /contact or a WhatsApp link">
          </div>
          <div class="col-md-6">
            <label>Maximum Registrations <small class="text-white-50">(0 = unlimited)</small></label>
            <input type="number" min="0" name="free_trial_max_registrations" class="form-control" value="<?= $v('free_trial_max_registrations', '0') ?>">
          </div>
          <div class="col-md-6">
            <label>Background Image</label>
            <?php if (!empty($settings['free_trial_background_image'])): ?>
              <div class="mb-2"><?= media_tile($settings['free_trial_background_image'], 'Free Trial Background', 'bi-image', '', null) ?></div>
            <?php endif; ?>
            <input type="file" name="free_trial_background_image" class="form-control" accept="image/jpeg,image/png,image/webp">
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-smtp">
      <div class="admin-card">
        <h6 class="mb-3">SMTP (Email)</h6>
        <p class="text-white-50 small">Leave blank to keep using the server's environment defaults.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <label>SMTP Host</label>
            <input type="text" name="smtp_host" class="form-control" value="<?= $v('smtp_host') ?>">
          </div>
          <div class="col-md-6">
            <label>SMTP Port</label>
            <input type="number" name="smtp_port" class="form-control" value="<?= $v('smtp_port', '587') ?>">
          </div>
          <div class="col-md-6">
            <label>SMTP Username</label>
            <input type="text" name="smtp_user" class="form-control" value="<?= $v('smtp_user') ?>">
          </div>
          <div class="col-md-6">
            <label>SMTP Password</label>
            <input type="password" name="smtp_pass" class="form-control" placeholder="<?= !empty($settings['smtp_pass']) ? '••••••••' : '' ?>">
          </div>
          <div class="col-md-6">
            <label>From Email</label>
            <input type="email" name="smtp_from_email" class="form-control" value="<?= $v('smtp_from_email') ?>">
          </div>
          <div class="col-md-6">
            <label>From Name</label>
            <input type="text" name="smtp_from_name" class="form-control" value="<?= $v('smtp_from_name', 'PowerSurge Gym') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="tab-backup">
      <div class="admin-card">
        <h6 class="mb-3">Backup &amp; Restore</h6>
        <p class="text-white-50 small">Backups are exported as a plain <code>.sql</code> file you can store anywhere. Restoring replaces <strong>all current data</strong> — a safety backup of the current state is saved automatically before any restore.</p>
      </div>
    </div>

    <div class="mt-3">
      <button type="submit" class="btn btn-ps">Save Settings</button>
    </div>
  </div>
</form>

<div class="admin-card mt-4" id="backupToolsCard">
  <h6 class="mb-3">Backup Tools</h6>
  <div class="d-flex flex-wrap gap-3">
    <a href="<?= url('/admin/settings/backup') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-download"></i> Download Backup (.sql)</a>
  </div>

  <hr>

  <form method="post" action="<?= url('/admin/settings/restore') ?>" enctype="multipart/form-data" class="admin-form"
        onsubmit="return confirm('This will REPLACE all current data with the uploaded backup. A safety backup is taken first, but this cannot be undone from the browser. Continue?');">
    <?= Security::csrfField() ?>
    <div class="row g-3 align-items-end">
      <div class="col-md-5">
        <label>Backup File (.sql)</label>
        <input type="file" name="backup_file" class="form-control" accept=".sql" required>
      </div>
      <div class="col-md-4">
        <label>Type <code>RESTORE</code> to confirm</label>
        <input type="text" name="confirm_phrase" class="form-control" required>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-upload"></i> Restore Database</button>
      </div>
    </div>
  </form>
</div>
