<?php
/**
 * Front controller — single entry point for the whole public site.
 * Matches Hostinger shared hosting's single-document-root model.
 */

require __DIR__ . '/config/config.php';
require __DIR__ . '/core/bootstrap.php';

$router = new Router();

// ---- Public pages ---------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);
$router->get('/membership', [PageController::class, 'membership']);
// Packages and Pricing were merged into the single Membership Plans page — old links still resolve.
$router->get('/packages', fn () => redirect('membership'));
$router->get('/pricing', fn () => redirect('membership'));
$router->get('/personal-training', [PageController::class, 'personalTraining']);
$router->get('/store', [StoreController::class, 'index']);
$router->get('/store/{slug}', [StoreController::class, 'show']);
$router->post('/store/{slug}/review', [StoreController::class, 'submitReview']);
$router->post('/store/{slug}/wishlist', [StoreController::class, 'toggleWishlist']);
$router->get('/gallery', [GalleryController::class, 'index']);
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/contact', [ContactController::class, 'index']);
$router->post('/free-trial/register', [FreeTrialController::class, 'register']);
$router->get('/trainers/{slug}', [TrainerController::class, 'show']);
$router->post('/trainers/{slug}/book', [TrainerController::class, 'book']);
$router->post('/trainers/{slug}/review', [TrainerController::class, 'submitReview']);

// ---- Cart -------------------------------------------------------------------
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);

// ---- Checkout -----------------------------------------------------------------
$router->get('/checkout', [CheckoutController::class, 'show']);
$router->post('/checkout/place', [CheckoutController::class, 'placeOrder']);
$router->get('/checkout/confirmation/{id}', [CheckoutController::class, 'confirmation']);

// ---- Guest Order Tracking -----------------------------------------------------------
$router->get('/track-order', [OrderTrackingController::class, 'show']);
$router->post('/track-order/find', [OrderTrackingController::class, 'find']);
$router->post('/track-order/invoice', [OrderTrackingController::class, 'invoice']);

// ---- Auth -------------------------------------------------------------------
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// ---- Auth-gated ---------------------------------------------------------------
$router->get('/account', [AccountController::class, 'index']);
$router->get('/account/profile', [AccountController::class, 'profile']);
$router->post('/account/profile', [AccountController::class, 'profileUpdate']);
$router->post('/account/password', [AccountController::class, 'passwordUpdate']);
$router->get('/account/orders', [AccountController::class, 'orders']);
$router->get('/account/orders/{id}', [AccountController::class, 'orderDetail']);
$router->get('/account/orders/{id}/invoice', [AccountController::class, 'orderInvoice']);
$router->get('/account/addresses', [AccountController::class, 'addresses']);
$router->post('/account/addresses', [AccountController::class, 'addressStore']);
$router->post('/account/addresses/{id}', [AccountController::class, 'addressUpdate']);
$router->post('/account/addresses/{id}/default', [AccountController::class, 'addressSetDefault']);
$router->post('/account/addresses/{id}/delete', [AccountController::class, 'addressDelete']);
$router->get('/account/wishlist', [AccountController::class, 'wishlist']);
$router->post('/account/wishlist/remove', [AccountController::class, 'wishlistRemove']);

$router->get('/admin', [AdminDashboardController::class, 'index']);

// ---- Admin: Trainers ------------------------------------------------------------
$router->get('/admin/trainers', [TrainerAdminController::class, 'index']);
$router->get('/admin/trainers/create', [TrainerAdminController::class, 'create']);
$router->post('/admin/trainers', [TrainerAdminController::class, 'store']);
$router->post('/admin/trainers/bulk', [TrainerAdminController::class, 'bulkAction']);
$router->get('/admin/trainers/{id}/edit', [TrainerAdminController::class, 'edit']);
$router->post('/admin/trainers/{id}', [TrainerAdminController::class, 'update']);
$router->post('/admin/trainers/{id}/delete', [TrainerAdminController::class, 'destroy']);
$router->get('/admin/trainers/{id}', [TrainerAdminController::class, 'show']);
$router->post('/admin/trainers/{id}/status', [TrainerAdminController::class, 'changeStatus']);
$router->post('/admin/trainers/{id}/featured', [TrainerAdminController::class, 'featured']);
$router->post('/admin/trainers/{id}/toggle-active', [TrainerAdminController::class, 'toggleActive']);
$router->post('/admin/trainers/{id}/reorder', [TrainerAdminController::class, 'reorder']);
$router->post('/admin/trainers/{id}/delete-photo', [TrainerAdminController::class, 'deletePhoto']);
$router->post('/admin/trainers/{id}/delete-cover', [TrainerAdminController::class, 'deleteCoverPhoto']);
$router->get('/admin/trainers/{id}/gallery', [TrainerAdminController::class, 'gallery']);
$router->post('/admin/trainers/{id}/gallery', [TrainerAdminController::class, 'galleryUpload']);
$router->post('/admin/trainers/{id}/gallery/{imageId}/delete', [TrainerAdminController::class, 'galleryDelete']);

// ---- Admin: Membership Packages ---------------------------------------------------
$router->get('/admin/packages', [MembershipAdminController::class, 'index']);
$router->get('/admin/packages/create', [MembershipAdminController::class, 'create']);
$router->post('/admin/packages', [MembershipAdminController::class, 'store']);
$router->get('/admin/packages/{id}/edit', [MembershipAdminController::class, 'edit']);
$router->post('/admin/packages/{id}', [MembershipAdminController::class, 'update']);
$router->post('/admin/packages/{id}/delete', [MembershipAdminController::class, 'destroy']);
$router->post('/admin/packages/{id}/toggle-active', [MembershipAdminController::class, 'toggleActive']);
$router->post('/admin/packages/{id}/featured', [MembershipAdminController::class, 'toggleFeatured']);
$router->post('/admin/packages/{id}/toggle-offer', [MembershipAdminController::class, 'toggleOffer']);
$router->post('/admin/packages/{id}/reorder', [MembershipAdminController::class, 'reorder']);

// ---- Admin: Coupons ---------------------------------------------------------------
$router->get('/admin/coupons', [CouponAdminController::class, 'index']);
$router->get('/admin/coupons/create', [CouponAdminController::class, 'create']);
$router->post('/admin/coupons', [CouponAdminController::class, 'store']);
$router->get('/admin/coupons/{id}/edit', [CouponAdminController::class, 'edit']);
$router->post('/admin/coupons/{id}', [CouponAdminController::class, 'update']);
$router->post('/admin/coupons/{id}/delete', [CouponAdminController::class, 'destroy']);
$router->post('/admin/coupons/{id}/toggle-active', [CouponAdminController::class, 'toggleActive']);
$router->post('/admin/coupons/{id}/duplicate', [CouponAdminController::class, 'duplicate']);

// ---- Admin: Members ---------------------------------------------------------------
$router->get('/admin/members', [MemberAdminController::class, 'index']);
$router->get('/admin/members/create', [MemberAdminController::class, 'create']);
$router->post('/admin/members', [MemberAdminController::class, 'store']);
$router->post('/admin/members/bulk', [MemberAdminController::class, 'bulkAction']);
$router->get('/admin/members/{id}/edit', [MemberAdminController::class, 'edit']);
$router->post('/admin/members/{id}', [MemberAdminController::class, 'update']);
$router->post('/admin/members/{id}/delete', [MemberAdminController::class, 'destroy']);
$router->get('/admin/members/{id}', [MemberAdminController::class, 'show']);
$router->post('/admin/members/{id}/renew', [MemberAdminController::class, 'renew']);
$router->post('/admin/members/{id}/checkin', [MemberAdminController::class, 'checkIn']);
$router->post('/admin/members/{id}/checkout', [MemberAdminController::class, 'checkOut']);
$router->post('/admin/members/{id}/charge-trainer-fee', [MemberAdminController::class, 'chargeTrainerFee']);
$router->post('/admin/members/{id}/charge-locker-fine', [MemberAdminController::class, 'chargeLockerFine']);

// ---- Admin: Store (Products & Categories) ------------------------------------------
$router->get('/admin/products', [ProductAdminController::class, 'index']);
$router->get('/admin/products/create', [ProductAdminController::class, 'create']);
$router->post('/admin/products', [ProductAdminController::class, 'store']);
$router->post('/admin/products/bulk', [ProductAdminController::class, 'bulkAction']);
$router->get('/admin/products/{id}/edit', [ProductAdminController::class, 'edit']);
$router->post('/admin/products/{id}', [ProductAdminController::class, 'update']);
$router->post('/admin/products/{id}/delete', [ProductAdminController::class, 'destroy']);
$router->post('/admin/products/{id}/status', [ProductAdminController::class, 'setStatus']);
$router->post('/admin/products/{id}/adjust-stock', [ProductAdminController::class, 'adjustStock']);
$router->post('/admin/products/{id}/gallery', [ProductAdminController::class, 'galleryUpload']);
$router->post('/admin/products/{id}/gallery/{imageId}/delete', [ProductAdminController::class, 'galleryDelete']);

$router->get('/admin/categories', [ProductCategoryAdminController::class, 'index']);
$router->post('/admin/categories', [ProductCategoryAdminController::class, 'store']);
$router->post('/admin/categories/{id}', [ProductCategoryAdminController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [ProductCategoryAdminController::class, 'destroy']);

$router->get('/admin/brands', [BrandAdminController::class, 'index']);
$router->post('/admin/brands', [BrandAdminController::class, 'store']);
$router->post('/admin/brands/{id}', [BrandAdminController::class, 'update']);
$router->post('/admin/brands/{id}/delete', [BrandAdminController::class, 'destroy']);

$router->get('/admin/delivery-zones', [DeliveryZoneAdminController::class, 'index']);
$router->post('/admin/delivery-zones', [DeliveryZoneAdminController::class, 'store']);
$router->post('/admin/delivery-zones/{id}', [DeliveryZoneAdminController::class, 'update']);
$router->post('/admin/delivery-zones/{id}/delete', [DeliveryZoneAdminController::class, 'destroy']);

$router->get('/admin/delivery-time-slots', [DeliveryTimeSlotAdminController::class, 'index']);
$router->post('/admin/delivery-time-slots', [DeliveryTimeSlotAdminController::class, 'store']);
$router->post('/admin/delivery-time-slots/{id}', [DeliveryTimeSlotAdminController::class, 'update']);
$router->post('/admin/delivery-time-slots/{id}/delete', [DeliveryTimeSlotAdminController::class, 'destroy']);

$router->get('/admin/delivery-staff', [DeliveryStaffAdminController::class, 'index']);
$router->get('/admin/delivery-staff/create', [DeliveryStaffAdminController::class, 'create']);
$router->post('/admin/delivery-staff', [DeliveryStaffAdminController::class, 'store']);
$router->get('/admin/delivery-staff/{id}/edit', [DeliveryStaffAdminController::class, 'edit']);
$router->post('/admin/delivery-staff/{id}', [DeliveryStaffAdminController::class, 'update']);
$router->post('/admin/delivery-staff/{id}/delete', [DeliveryStaffAdminController::class, 'destroy']);

$router->get('/admin/products/sales', [ProductAdminController::class, 'sales']);

// ---- Admin: POS -----------------------------------------------------------------
$router->get('/admin/pos', [PosController::class, 'index']);
$router->post('/admin/pos/checkout', [PosController::class, 'checkout']);
$router->get('/admin/pos/receipt/{id}', [PosController::class, 'receipt']);
$router->get('/admin/pos/receipt/{id}/pdf', [PosController::class, 'pdf']);

// ---- Admin: Orders (online store) ------------------------------------------------
$router->get('/admin/orders', [OrderAdminController::class, 'index']);
$router->post('/admin/orders/bulk', [OrderAdminController::class, 'bulkAction']);
$router->get('/admin/orders/{id}', [OrderAdminController::class, 'show']);
$router->post('/admin/orders/{id}/status', [OrderAdminController::class, 'updateStatus']);
$router->post('/admin/orders/{id}/payment-status', [OrderAdminController::class, 'updatePaymentStatus']);
$router->post('/admin/orders/{id}/refund', [OrderAdminController::class, 'refund']);
$router->post('/admin/orders/{id}/notes', [OrderAdminController::class, 'updateNotes']);
$router->get('/admin/orders/{id}/receipt', [OrderAdminController::class, 'receipt']);
$router->get('/admin/orders/{id}/pdf', [OrderAdminController::class, 'pdf']);

// ---- Admin: Reports ---------------------------------------------------------------
$router->get('/admin/reports', [ReportController::class, 'index']);
$router->get('/admin/reports/sales', [ReportController::class, 'salesReport']);
$router->get('/admin/reports/revenue', [ReportController::class, 'revenue']);
$router->get('/admin/reports/members', [ReportController::class, 'membersReport']);
$router->get('/admin/reports/renewals', [ReportController::class, 'renewals']);
$router->get('/admin/reports/attendance', [ReportController::class, 'attendance']);
$router->get('/admin/reports/trainer-income', [ReportController::class, 'trainerIncome']);
$router->get('/admin/reports/store-sales', [ReportController::class, 'storeSales']);
$router->get('/admin/reports/online-orders', [ReportController::class, 'onlineOrders']);
$router->get('/admin/reports/stock', [ReportController::class, 'stock']);

// ---- Admin: Settings ---------------------------------------------------------------
$router->get('/admin/settings', [SettingsAdminController::class, 'index']);
$router->post('/admin/settings', [SettingsAdminController::class, 'update']);
$router->get('/admin/settings/free-trial-registrations', [SettingsAdminController::class, 'freeTrialRegistrations']);
$router->get('/admin/settings/backup', [SettingsAdminController::class, 'backup']);
$router->post('/admin/settings/restore', [SettingsAdminController::class, 'restore']);

// ---- Admin: Messages ---------------------------------------------------------------
$router->get('/admin/messages', [ContactMessageAdminController::class, 'index']);
$router->get('/admin/messages/{id}', [ContactMessageAdminController::class, 'show']);
$router->post('/admin/messages/{id}/mark-replied', [ContactMessageAdminController::class, 'markReplied']);
$router->post('/admin/messages/{id}/delete', [ContactMessageAdminController::class, 'destroy']);

// ---- Admin: Reviews ---------------------------------------------------------------
$router->get('/admin/reviews', [ReviewAdminController::class, 'index']);
$router->get('/admin/audit-log', [AuditLogController::class, 'index']);
$router->post('/admin/reviews/{id}/approve', [ReviewAdminController::class, 'approve']);
$router->post('/admin/reviews/{id}/hide', [ReviewAdminController::class, 'hide']);
$router->post('/admin/reviews/{id}/delete', [ReviewAdminController::class, 'destroy']);
$router->post('/admin/reviews/{id}/reply', [ReviewAdminController::class, 'reply']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
