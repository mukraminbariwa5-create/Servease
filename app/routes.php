<?php
/** @var Router $router */

// Installer (only works locally / before install)
$router->get('/install', [InstallController::class, 'show']);
$router->post('/install', [InstallController::class, 'run']);

// App (installable): manifest, service worker, offline screen
$router->get('/manifest.webmanifest', [AppController::class, 'manifest']);
$router->get('/sw.js', [AppController::class, 'serviceWorker']);
$router->get('/offline', [AppController::class, 'offline']);

// Public
$router->get('/', [HomeController::class, 'index']);
$router->get('/how-it-works', [HomeController::class, 'howItWorks']);
$router->get('/services', [ServiceController::class, 'index']);
$router->get('/categories', [ServiceController::class, 'categories']);
$router->get('/search/suggest', [SearchController::class, 'suggest']);
$router->get('/providers', [ProviderController::class, 'index']);
$router->get('/providers/{id}', [ProviderController::class, 'show']);
$router->get('/jobs', [JobController::class, 'index']);
$router->get('/jobs/new', [JobController::class, 'create']);
$router->post('/jobs', [JobController::class, 'store']);
$router->get('/jobs/{id}', [JobController::class, 'show']);

// Language (English / Filipino)
$router->post('/lang', [AccountController::class, 'language']);

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/welcome', [AuthController::class, 'welcome']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);
$router->get('/verify-email', [AuthController::class, 'verify']);
$router->post('/verify-email/resend', [AuthController::class, 'resendVerification']);

// Signed-in: dashboard, account
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/account', [AccountController::class, 'menu']);
$router->get('/settings', [AccountController::class, 'edit']);
$router->post('/settings', [AccountController::class, 'update']);
$router->post('/settings/password', [AccountController::class, 'password']);
$router->post('/settings/avatar', [AccountController::class, 'avatar']);
$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/read-all', [NotificationController::class, 'readAll']);
$router->get('/notifications/{id}/open', [NotificationController::class, 'open']);
$router->get('/saved', [ProviderController::class, 'saved']);
$router->post('/providers/{id}/save', [ProviderController::class, 'toggleSave']);

// Jobs & quotes
$router->get('/my/jobs', [JobController::class, 'mine']);
$router->post('/jobs/{id}/cancel', [JobController::class, 'cancel']);
$router->post('/jobs/{id}/quote', [QuoteController::class, 'store']);
$router->get('/my/quotes', [QuoteController::class, 'mine']);
$router->get('/quotes/{id}/accept', [QuoteController::class, 'schedule']);
$router->post('/quotes/{id}/accept', [QuoteController::class, 'accept']);
$router->post('/quotes/{id}/decline', [QuoteController::class, 'decline']);
$router->post('/quotes/{id}/withdraw', [QuoteController::class, 'withdraw']);

// Bookings, payments & reviews
$router->get('/providers/{id}/book', [BookingController::class, 'create']);
$router->post('/providers/{id}/book', [BookingController::class, 'store']);
$router->get('/bookings', [BookingController::class, 'index']);
$router->get('/bookings/{id}', [BookingController::class, 'show']);
$router->post('/bookings/{id}/status', [BookingController::class, 'transition']);
$router->get('/bookings/{id}/reschedule', [RescheduleController::class, 'form']);
$router->post('/bookings/{id}/reschedule', [RescheduleController::class, 'propose']);
$router->post('/bookings/{id}/reschedule/answer', [RescheduleController::class, 'respond']);
$router->post('/bookings/{id}/report', [IssueController::class, 'store']);
$router->get('/bookings/{id}/pay', [PaymentController::class, 'choose']);
$router->post('/bookings/{id}/pay', [PaymentController::class, 'recordDirect']);
$router->post('/bookings/{id}/pay-online', [PaymentController::class, 'startOnline']);
$router->get('/bookings/{id}/payment-return', [PaymentController::class, 'returnFromCheckout']);
$router->post('/payments/{id}/confirm', [PaymentController::class, 'confirm']);
$router->post('/payments/{id}/reject', [PaymentController::class, 'reject']);
$router->post('/webhooks/paymongo', [PaymentController::class, 'webhook']);
$router->post('/bookings/{id}/review', [ReviewController::class, 'store']);
$router->post('/reviews/{id}/reply', [ReviewController::class, 'reply']);

// Messages
$router->get('/messages', [MessageController::class, 'index']);
$router->post('/messages/start', [MessageController::class, 'start']);
$router->get('/messages/{id}', [MessageController::class, 'show']);
$router->post('/messages/{id}', [MessageController::class, 'send']);
$router->get('/messages/{id}/poll', [MessageController::class, 'poll']);

// Provider tools
$router->get('/provider/profile', [ProviderProfileController::class, 'edit']);
$router->post('/provider/profile', [ProviderProfileController::class, 'update']);
$router->post('/provider/services', [ProviderProfileController::class, 'addService']);
$router->post('/provider/services/{id}/delete', [ProviderProfileController::class, 'deleteService']);
$router->post('/provider/portfolio', [ProviderProfileController::class, 'addPortfolio']);
$router->post('/provider/portfolio/{id}/delete', [ProviderProfileController::class, 'deletePortfolio']);
$router->post('/provider/verification', [ProviderProfileController::class, 'submitVerification']);
$router->post('/provider/hours', [ProviderProfileController::class, 'updateHours']);
$router->post('/provider/days-off', [ProviderProfileController::class, 'addDayOff']);
$router->post('/provider/days-off/{day}/delete', [ProviderProfileController::class, 'removeDayOff']);
$router->post('/provider/availability', [ProviderProfileController::class, 'toggleAvailability']);

// Admin
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/{id}/status', [AdminController::class, 'userStatus']);
$router->get('/admin/verifications', [AdminController::class, 'verifications']);
$router->post('/admin/verifications/{id}', [AdminController::class, 'verify']);
$router->get('/admin/verifications/{id}/document', [AdminController::class, 'document']);
$router->get('/admin/categories', [AdminController::class, 'categories']);
$router->post('/admin/categories', [AdminController::class, 'saveCategory']);
$router->post('/admin/categories/{id}/toggle', [AdminController::class, 'toggleCategory']);
$router->get('/admin/bookings', [AdminController::class, 'bookings']);
$router->get('/admin/jobs', [AdminController::class, 'jobs']);
$router->get('/admin/reviews', [AdminController::class, 'reviews']);
$router->post('/admin/reviews/{id}/toggle', [AdminController::class, 'toggleReview']);
$router->get('/admin/payments', [AdminController::class, 'payments']);
$router->get('/admin/issues', [AdminController::class, 'issues']);
$router->post('/admin/issues/{id}/resolve', [AdminController::class, 'resolveIssue']);
