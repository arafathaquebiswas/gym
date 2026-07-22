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
$router->get('/gallery', [GalleryController::class, 'index']);
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);
$router->get('/faq', [PageController::class, 'faq']);
$router->get('/contact', [ContactController::class, 'index']);
$router->get('/trainers/{slug}', [TrainerController::class, 'show']);
$router->post('/trainers/{slug}/book', [TrainerController::class, 'book']);
$router->post('/trainers/{slug}/review', [TrainerController::class, 'submitReview']);

// ---- Auth -------------------------------------------------------------------
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// ---- Auth-gated ---------------------------------------------------------------
$router->get('/account', [AuthController::class, 'account']);
$router->get('/admin', [AdminDashboardController::class, 'index']);

// ---- Admin: Trainers ------------------------------------------------------------
$router->get('/admin/trainers', [TrainerAdminController::class, 'index']);
$router->get('/admin/trainers/create', [TrainerAdminController::class, 'create']);
$router->post('/admin/trainers', [TrainerAdminController::class, 'store']);
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

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
