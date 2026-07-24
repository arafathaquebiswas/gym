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
$router->get('/bundles', [StoreController::class, 'bundles']);
$router->get('/store/{slug}', [StoreController::class, 'show']);
$router->post('/store/{slug}/review', [StoreController::class, 'submitReview']);
$router->post('/store/{slug}/wishlist', [StoreController::class, 'toggleWishlist']);
$router->post('/store/{slug}/notify-back-in-stock', [StoreController::class, 'notifyBackInStock']);
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
$router->post('/cart/add-bundle/{id}', [CartController::class, 'addBundle']);
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

// ---- Auth (staff/delivery only — no member-facing login in this app) --------------
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// ---- Online Membership Registration (public, account-free) ------------------------
$router->get('/register', [MembershipRegistrationController::class, 'show']);
$router->post('/register', [MembershipRegistrationController::class, 'submit']);

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
$router->get('/admin/members/{id}/payments', [MemberAdminController::class, 'paymentHistory']);

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
$router->get('/admin/products/{id}/history', [ProductAdminController::class, 'history']);
$router->post('/admin/products/{id}/toggle-featured', [ProductAdminController::class, 'toggleFeatured']);
$router->post('/admin/products/{id}/toggle-archived', [ProductAdminController::class, 'toggleArchived']);
$router->post('/admin/products/{id}/duplicate', [ProductAdminController::class, 'duplicate']);
$router->post('/admin/products/{id}/attributes', [ProductAdminController::class, 'assignAttributes']);
$router->post('/admin/products/{id}/tags-related', [ProductAdminController::class, 'updateTagsAndRelated']);
$router->post('/admin/products/{id}/variants', [ProductVariantAdminController::class, 'store']);
$router->post('/admin/products/{id}/variants/{variantId}', [ProductVariantAdminController::class, 'update']);
$router->post('/admin/products/{id}/variants/{variantId}/delete', [ProductVariantAdminController::class, 'destroy']);
$router->post('/admin/products/{id}/variants/{variantId}/adjust-stock', [ProductVariantAdminController::class, 'adjustStock']);
$router->post('/admin/products/{id}/gallery', [ProductAdminController::class, 'galleryUpload']);
$router->post('/admin/products/{id}/gallery/{imageId}/delete', [ProductAdminController::class, 'galleryDelete']);

$router->get('/admin/categories', [ProductCategoryAdminController::class, 'index']);
$router->post('/admin/categories', [ProductCategoryAdminController::class, 'store']);
$router->post('/admin/categories/{id}', [ProductCategoryAdminController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [ProductCategoryAdminController::class, 'destroy']);
$router->post('/admin/categories/{id}/toggle-status', [ProductCategoryAdminController::class, 'toggleStatus']);
$router->post('/admin/categories/{id}/move-up', [ProductCategoryAdminController::class, 'moveUp']);
$router->post('/admin/categories/{id}/move-down', [ProductCategoryAdminController::class, 'moveDown']);

$router->get('/admin/attributes', [ProductAttributeAdminController::class, 'index']);
$router->post('/admin/attributes', [ProductAttributeAdminController::class, 'store']);
$router->post('/admin/attributes/{id}', [ProductAttributeAdminController::class, 'update']);
$router->post('/admin/attributes/{id}/delete', [ProductAttributeAdminController::class, 'destroy']);
$router->post('/admin/attributes/{id}/values', [ProductAttributeAdminController::class, 'storeValue']);
$router->post('/admin/attribute-values/{id}', [ProductAttributeAdminController::class, 'updateValue']);
$router->post('/admin/attribute-values/{id}/delete', [ProductAttributeAdminController::class, 'destroyValue']);

$router->get('/admin/brands', [BrandAdminController::class, 'index']);
$router->post('/admin/brands', [BrandAdminController::class, 'store']);
$router->post('/admin/brands/{id}', [BrandAdminController::class, 'update']);
$router->post('/admin/brands/{id}/delete', [BrandAdminController::class, 'destroy']);

$router->get('/admin/suppliers', [SupplierAdminController::class, 'index']);
$router->post('/admin/suppliers', [SupplierAdminController::class, 'store']);
$router->post('/admin/suppliers/{id}', [SupplierAdminController::class, 'update']);
$router->post('/admin/suppliers/{id}/delete', [SupplierAdminController::class, 'destroy']);

$router->get('/admin/purchases', [PurchaseAdminController::class, 'index']);
$router->get('/admin/purchases/create', [PurchaseAdminController::class, 'create']);
$router->post('/admin/purchases', [PurchaseAdminController::class, 'store']);
$router->get('/admin/purchases/{id}', [PurchaseAdminController::class, 'show']);

$router->get('/admin/flash-sales', [FlashSaleAdminController::class, 'index']);
$router->post('/admin/flash-sales', [FlashSaleAdminController::class, 'store']);
$router->post('/admin/flash-sales/{id}', [FlashSaleAdminController::class, 'update']);
$router->post('/admin/flash-sales/{id}/delete', [FlashSaleAdminController::class, 'destroy']);
$router->post('/admin/flash-sales/{id}/toggle-active', [FlashSaleAdminController::class, 'toggleActive']);

$router->get('/admin/bundles', [BundleAdminController::class, 'index']);
$router->get('/admin/bundles/create', [BundleAdminController::class, 'create']);
$router->post('/admin/bundles', [BundleAdminController::class, 'store']);
$router->get('/admin/bundles/{id}/edit', [BundleAdminController::class, 'edit']);
$router->post('/admin/bundles/{id}', [BundleAdminController::class, 'update']);
$router->post('/admin/bundles/{id}/delete', [BundleAdminController::class, 'destroy']);
$router->post('/admin/bundles/{id}/toggle-active', [BundleAdminController::class, 'toggleActive']);

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
$router->post('/admin/delivery-staff/{id}/toggle-active', [DeliveryStaffAdminController::class, 'toggleActive']);

$router->get('/delivery', [DeliveryController::class, 'dashboard']);
$router->get('/delivery/history', [DeliveryController::class, 'history']);
$router->get('/delivery/profile', [DeliveryController::class, 'profile']);
$router->post('/delivery/profile', [DeliveryController::class, 'profileUpdate']);
$router->post('/delivery/password', [DeliveryController::class, 'passwordUpdate']);
$router->get('/delivery/orders/{id}', [DeliveryController::class, 'orderDetail']);
$router->post('/delivery/{id}/status', [DeliveryController::class, 'updateStatus']);

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
$router->post('/admin/orders/{id}/assign-delivery-person', [OrderAdminController::class, 'assignDeliveryPerson']);
$router->post('/admin/orders/{id}/confirm-pickup', [OrderAdminController::class, 'confirmPickup']);
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
$router->get('/admin/reports/products', [ReportController::class, 'productReport']);
$router->get('/admin/reports/delivery', [ReportController::class, 'deliveryReport']);
$router->get('/admin/reports/pickup', [ReportController::class, 'pickupReport']);
$router->get('/admin/reports/customers', [ReportController::class, 'customerReport']);
$router->get('/admin/reports/coupons', [ReportController::class, 'couponReport']);
$router->get('/admin/reports/offer-performance', [ReportController::class, 'offerPerformance']);
$router->get('/admin/reports/monthly-revenue', [ReportController::class, 'monthlyRevenue']);

// ---- Admin: Settings ---------------------------------------------------------------
$router->get('/admin/settings', [SettingsAdminController::class, 'index']);
$router->post('/admin/settings', [SettingsAdminController::class, 'update']);
$router->get('/admin/settings/free-trial-registrations', [SettingsAdminController::class, 'freeTrialRegistrations']);
$router->get('/admin/settings/backup', [SettingsAdminController::class, 'backup']);
$router->post('/admin/settings/restore', [SettingsAdminController::class, 'restore']);

// ---- Admin: Role & Permission Management (Main Admin + Super Admin only) ----------
$router->get('/admin/roles', [RoleAdminController::class, 'index']);
$router->get('/admin/roles/staff/create', [RoleAdminController::class, 'createStaff']);
$router->post('/admin/roles/staff', [RoleAdminController::class, 'storeStaff']);
$router->get('/admin/roles/staff/{id}/edit', [RoleAdminController::class, 'editStaff']);
$router->post('/admin/roles/staff/{id}', [RoleAdminController::class, 'updateStaff']);
$router->post('/admin/roles/staff/{id}/toggle-suspend', [RoleAdminController::class, 'toggleStaffStatus']);
$router->post('/admin/roles/staff/{id}/delete', [RoleAdminController::class, 'deleteStaff']);
$router->get('/admin/roles/super-admin/create', [RoleAdminController::class, 'createSuperAdmin']);
$router->post('/admin/roles/super-admin', [RoleAdminController::class, 'storeSuperAdmin']);
$router->get('/admin/roles/super-admin/{id}/edit', [RoleAdminController::class, 'editSuperAdmin']);
$router->post('/admin/roles/super-admin/{id}', [RoleAdminController::class, 'updateSuperAdmin']);
$router->post('/admin/roles/super-admin/{id}/toggle-suspend', [RoleAdminController::class, 'toggleSuperAdminStatus']);
$router->post('/admin/roles/super-admin/{id}/delete', [RoleAdminController::class, 'deleteSuperAdmin']);
$router->get('/admin/roles/{id}/permissions', [RoleAdminController::class, 'permissions']);
$router->post('/admin/roles/{id}/permissions', [RoleAdminController::class, 'savePermissions']);
$router->get('/admin/roles/locks', [RoleAdminController::class, 'moduleLocks']);
$router->post('/admin/roles/locks', [RoleAdminController::class, 'saveModuleLocks']);

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
