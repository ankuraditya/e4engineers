<?php

use App\Http\Controllers\Api\V1\Account\AddressController;
use App\Http\Controllers\Api\V1\Account\DigitalLibraryController;
use App\Http\Controllers\Api\V1\Account\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Api\V1\Account\PaymentController as AccountPaymentController;
use App\Http\Controllers\Api\V1\Account\ProfileController;
use App\Http\Controllers\Api\V1\Account\SupportController as AccountSupportController;
use App\Http\Controllers\Api\V1\Admin\AdminAuthenticationController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\ArticleSeoController;
use App\Http\Controllers\Api\V1\Admin\BannerController;
use App\Http\Controllers\Api\V1\Admin\BookSeoController;
use App\Http\Controllers\Api\V1\Admin\CmsPageController;
use App\Http\Controllers\Api\V1\Admin\ContributorSeoController;
use App\Http\Controllers\Api\V1\Admin\CouponController;
use App\Http\Controllers\Api\V1\Admin\CourseSeoController;
use App\Http\Controllers\Api\V1\Admin\DigitalResourceSeoController;
use App\Http\Controllers\Api\V1\Admin\EntitlementController;
use App\Http\Controllers\Api\V1\Admin\InternshipCertificateController as AdminInternshipCertificateController;
use App\Http\Controllers\Api\V1\Admin\InventoryController;
use App\Http\Controllers\Api\V1\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Api\V1\Admin\InvoiceSettingsController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\OperationsController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\PageSectionController;
use App\Http\Controllers\Api\V1\Admin\PaymentOperationsController;
use App\Http\Controllers\Api\V1\Admin\PaymentSettingsController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\PublicationSeoController;
use App\Http\Controllers\Api\V1\Admin\RedirectController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\SeoController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Api\V1\Admin\SocialLinkController;
use App\Http\Controllers\Api\V1\Auth\AuthenticationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartCouponController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InternshipCertificateController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\OperationalController;
use App\Http\Controllers\Api\V1\OrderAccessController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\Public\ArticleController;
use App\Http\Controllers\Api\V1\Public\AuthorController;
use App\Http\Controllers\Api\V1\Public\BookController;
use App\Http\Controllers\Api\V1\Public\CategoryController;
use App\Http\Controllers\Api\V1\Public\CmsController;
use App\Http\Controllers\Api\V1\Public\ContributorController;
use App\Http\Controllers\Api\V1\Public\CourseController;
use App\Http\Controllers\Api\V1\Public\CourseLevelController;
use App\Http\Controllers\Api\V1\Public\DigitalDownloadController;
use App\Http\Controllers\Api\V1\Public\DigitalResourceController;
use App\Http\Controllers\Api\V1\Public\DiscoveryController;
use App\Http\Controllers\Api\V1\Public\EngineeringDisciplineController;
use App\Http\Controllers\Api\V1\Public\PublicationController;
use App\Http\Controllers\Api\V1\Public\PublicationTypeController;
use App\Http\Controllers\Api\V1\Public\PublisherController;
use App\Http\Controllers\Api\V1\Public\ResourceTypeController;
use App\Http\Controllers\Api\V1\Public\SitemapController;
use App\Http\Controllers\Api\V1\Public\TagController;
use App\Http\Controllers\Api\V1\Public\TopicController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\ShippingWebhookController;
use App\Http\Controllers\Api\V1\TrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');
    Route::get('/notices', [DiscoveryController::class, 'notices']);
    Route::get('/notices/{slug}', [DiscoveryController::class, 'notice']);
    Route::get('/gallery', [DiscoveryController::class, 'gallery']);
    Route::get('/gallery/{slug}', [DiscoveryController::class, 'album']);
    Route::get('/videos', [DiscoveryController::class, 'videos']);
    Route::get('/videos/{slug}', [DiscoveryController::class, 'video']);
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->middleware('throttle:public-search');
    Route::get('/search', [SearchController::class, 'index'])->middleware('throttle:public-search');
    Route::post('/contact', [OperationalController::class, 'contact'])->middleware('throttle:public-contact');
    Route::post('/support', [OperationalController::class, 'support'])->middleware('throttle:public-support');
    Route::get('/workshops', [OperationalController::class, 'workshops']);
    Route::get('/workshops/{slug}', [OperationalController::class, 'workshop']);
    Route::post('/workshops/{workshop}/registrations', [OperationalController::class, 'registerWorkshop'])->middleware('throttle:workshop-registration');
    Route::get('/careers', [OperationalController::class, 'careers']);
    Route::get('/careers/{slug}', [OperationalController::class, 'career']);
    Route::post('/careers/{job}/applications', [OperationalController::class, 'apply'])->middleware('throttle:career-application');
    Route::post('/internships/certificates/download', [InternshipCertificateController::class, 'download'])->middleware('throttle:certificate-lookup');

    $publicMasterControllers = [
        'engineering-disciplines' => EngineeringDisciplineController::class,
        'categories' => CategoryController::class,
        'topics' => TopicController::class,
        'tags' => TagController::class,
        'course-levels' => CourseLevelController::class,
        'resource-types' => ResourceTypeController::class,
        'publication-types' => PublicationTypeController::class,
    ];
    foreach ($publicMasterControllers as $path => $controller) {
        Route::get("/{$path}", [$controller, 'index']);
    }
    Route::get('/engineering-disciplines/{slug}', [EngineeringDisciplineController::class, 'show']);
    Route::get('/pages/{slug}', [CmsController::class, 'page']);
    Route::get('/settings/public', [CmsController::class, 'settings']);
    Route::get('/sitemap.xml', SitemapController::class);
    Route::get('/social-links', [CmsController::class, 'socialLinks']);
    Route::get('/banners', [CmsController::class, 'banners']);
    Route::get('/contributors', [ContributorController::class, 'index']);
    Route::get('/contributors/{slug}', [ContributorController::class, 'show']);
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{slug}', [ArticleController::class, 'show']);
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{slug}', [CourseController::class, 'show']);
    Route::get('/publications', [PublicationController::class, 'index']);
    Route::get('/publications/{slug}', [PublicationController::class, 'show']);
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{slug}', [BookController::class, 'show']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{item}', [CartController::class, 'update']);
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::post('/cart/coupon', [CartCouponController::class, 'store'])->middleware('throttle:10,1');
    Route::delete('/cart/coupon', [CartCouponController::class, 'destroy'])->middleware('throttle:20,1');
    Route::post('/shipping/serviceability', [ShippingController::class, 'serviceability'])->middleware('throttle:20,1');
    Route::get('/shipping/self-collection', [ShippingController::class, 'selfCollection'])->middleware('throttle:30,1');
    Route::post('/cart/shipping/quote', [ShippingController::class, 'quote'])->middleware('throttle:20,1');
    Route::post('/checkout/shipping-options', [ShippingController::class, 'quote'])->middleware(['auth:sanctum', 'throttle:20,1']);
    Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])->middleware('throttle:10,1');
    Route::get('/orders/{orderNumber}/success', [OrderAccessController::class, 'success'])->middleware('throttle:30,1');
    Route::get('/payments/methods', [PaymentController::class, 'methods']);
    Route::get('/payments/scan-code', [PaymentController::class, 'scanCode']);
    Route::post('/orders/{orderNumber}/payments/scan-proof', [PaymentController::class, 'submitProof'])->middleware('throttle:5,1');
    Route::post('/orders/{orderNumber}/payments', [PaymentController::class, 'initiate'])->middleware('throttle:10,1');
    Route::post('/orders/{orderNumber}/payments/{attempt}/verify', [PaymentController::class, 'verify'])->middleware('throttle:20,1');
    Route::get('/orders/{orderNumber}/payment-status', [PaymentController::class, 'status'])->middleware('throttle:30,1');
    Route::post('/orders/{orderNumber}/payments/retry', [PaymentController::class, 'retry'])->middleware('throttle:10,1');
    Route::post('/payments/webhooks/{provider}', PaymentWebhookController::class);
    Route::post('/payments/callbacks/payu', [PaymentWebhookController::class, 'payuCallback']);
    Route::get('/orders/{orderNumber}/invoice', [InvoiceController::class, 'show'])->middleware('throttle:30,1');
    Route::get('/orders/{orderNumber}/invoice/download', [InvoiceController::class, 'download'])->middleware('throttle:downloads');
    Route::get('/orders/{orderNumber}/tracking', [TrackingController::class, 'show'])->middleware('throttle:30,1');
    Route::post('/shipping/webhooks/{provider}', ShippingWebhookController::class)->middleware('throttle:120,1');
    Route::get('/authors', [AuthorController::class, 'index']);
    Route::get('/authors/{slug}', [AuthorController::class, 'show']);
    Route::get('/publishers', [PublisherController::class, 'index']);
    Route::get('/publishers/{slug}', [PublisherController::class, 'show']);
    Route::get('/resources', [DigitalResourceController::class, 'index']);
    Route::get('/resources/{slug}', [DigitalResourceController::class, 'show']);
    Route::get('/resources/{slug}/download', [DigitalDownloadController::class, 'resource'])->middleware('throttle:downloads');
    Route::get('/publications/{slug}/download', [DigitalDownloadController::class, 'publication'])->middleware('throttle:downloads');

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthenticationController::class, 'register'])->middleware('throttle:auth-register');
        Route::post('/login', [AuthenticationController::class, 'login'])->middleware('throttle:auth-login');
        Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:auth-password-reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:auth-password-reset');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthenticationController::class, 'me']);
            Route::post('/logout', [AuthenticationController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/cart/merge', [CartController::class, 'merge']);
        Route::get('/account/digital-resources', [DigitalLibraryController::class, 'resources']);
        Route::get('/account/downloads', [DigitalLibraryController::class, 'downloads']);
        Route::get('/account/profile', [ProfileController::class, 'show']);
        Route::match(['put', 'patch'], '/account/profile', [ProfileController::class, 'update']);
        Route::put('/account/password', [ProfileController::class, 'changePassword'])->middleware('throttle:auth-password-reset');
        Route::get('/account/addresses', [AddressController::class, 'index']);
        Route::post('/account/addresses', [AddressController::class, 'store']);
        Route::get('/account/addresses/{address}', [AddressController::class, 'show']);
        Route::match(['put', 'patch'], '/account/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy']);
        Route::patch('/account/addresses/{address}/default', [AddressController::class, 'setDefault']);
        Route::get('/account/orders', [AccountOrderController::class, 'index']);
        Route::get('/account/dashboard', [AccountOrderController::class, 'dashboard']);
        Route::get('/account/orders/{orderNumber}', [AccountOrderController::class, 'show']);
        Route::get('/account/payments', [AccountPaymentController::class, 'index']);
        Route::get('/account/notification-preferences', [NotificationPreferenceController::class, 'show']);
        Route::put('/account/notification-preferences', [NotificationPreferenceController::class, 'update']);
        Route::get('/account/support-tickets', [AccountSupportController::class, 'index']);
        Route::get('/account/support-tickets/{ticket}', [AccountSupportController::class, 'show']);
        Route::post('/account/support-tickets/{ticket}/messages', [AccountSupportController::class, 'reply'])->middleware('throttle:public-support');
        Route::get('/account/support-attachments/{attachment}', [AccountSupportController::class, 'attachment'])->middleware('throttle:downloads');
    });

    Route::prefix('admin')->group(function (): void {
        Route::post('/auth/login', [AdminAuthenticationController::class, 'login'])->middleware('throttle:admin-login');

        Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
            Route::get('/auth/me', [AdminAuthenticationController::class, 'me']);
            Route::post('/auth/logout', [AdminAuthenticationController::class, 'logout']);
            Route::get('/dashboard', AdminDashboardController::class)->middleware('permission:admin.dashboard.access');
            Route::get('/orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view');
            Route::get('/orders/{orderNumber}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view');
            Route::patch('/orders/{orderNumber}/status', [AdminOrderController::class, 'status'])->middleware('permission:orders.update-status');
            Route::post('/orders/{orderNumber}/cod-collected', [AdminOrderController::class, 'collectCod'])->middleware('permission:orders.update-status');
            Route::post('/orders/{orderNumber}/pickup-ready', [AdminOrderController::class, 'pickupReady'])->middleware('permission:orders.update-status');
            Route::post('/orders/{orderNumber}/pickup-collected', [AdminOrderController::class, 'pickupCollected'])->middleware('permission:orders.update-status');
            Route::get('/payments/settings', [PaymentSettingsController::class, 'settings'])->middleware('permission:payment-settings.view');
            Route::put('/payments/settings', [PaymentSettingsController::class, 'updateSettings'])->middleware('permission:payment-settings.update');
            Route::get('/payments/providers', [PaymentSettingsController::class, 'providers'])->middleware('permission:payment-providers.view');
            Route::get('/payments/providers/{provider}', [PaymentSettingsController::class, 'show'])->middleware('permission:payment-providers.view');
            Route::put('/payments/providers/{provider}/credentials', [PaymentSettingsController::class, 'credentials'])->middleware('permission:payment-providers.configure');
            Route::post('/payments/providers/{provider}/scan-code', [PaymentSettingsController::class, 'scanCode'])->middleware('permission:payment-providers.configure');
            Route::patch('/payments/providers/{provider}/toggle', [PaymentSettingsController::class, 'toggle'])->middleware('permission:payment-providers.toggle');
            Route::patch('/payments/providers/{provider}/environment', [PaymentSettingsController::class, 'environment'])->middleware('permission:payment-providers.configure');
            Route::post('/payments/providers/{provider}/test-connection', [PaymentSettingsController::class, 'test'])->middleware(['permission:payment-providers.test', 'throttle:10,1']);
            Route::patch('/payments/providers/{provider}/default', [PaymentSettingsController::class, 'default'])->middleware('permission:payment-providers.default');
            Route::get('/payments/attempts', [PaymentOperationsController::class, 'attempts'])->middleware('permission:payment-attempts.view');
            Route::get('/payments/attempts/{attempt}/proof', [PaymentOperationsController::class, 'proof'])->middleware('permission:payment-attempts.view');
            Route::post('/payments/attempts/{attempt}/review', [PaymentOperationsController::class, 'review'])->middleware('permission:payment-reconciliation.run');
            Route::get('/payments/transactions', [PaymentOperationsController::class, 'transactions'])->middleware('permission:payment-transactions.view');
            Route::post('/payments/attempts/{attempt}/reconcile', [PaymentOperationsController::class, 'reconcile'])->middleware('permission:payment-reconciliation.run');
            Route::get('/invoice-settings', [InvoiceSettingsController::class, 'show'])->middleware('permission:invoice-settings.view');
            Route::post('/invoice-settings', [InvoiceSettingsController::class, 'update'])->middleware('permission:invoice-settings.update');
            Route::get('/invoices', [AdminInvoiceController::class, 'index'])->middleware('permission:invoices.view');
            Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])->middleware('permission:invoices.view');
            Route::post('/orders/{order}/invoice', [AdminInvoiceController::class, 'issue'])->middleware('permission:invoices.issue');
            Route::get('/invoices/{invoice}/download', [AdminInvoiceController::class, 'download'])->middleware('permission:invoices.download');
            Route::post('/invoices/{invoice}/regenerate', [AdminInvoiceController::class, 'regenerate'])->middleware('permission:invoices.regenerate');
            Route::post('/invoices/{invoice}/void', [AdminInvoiceController::class, 'void'])->middleware('permission:invoices.void');
            Route::get('/shipments', [AdminShipmentController::class, 'index'])->middleware('permission:shipments.view');
            Route::get('/shipments/{shipment}', [AdminShipmentController::class, 'show'])->middleware('permission:shipments.view');
            Route::post('/orders/{order}/shipment', [AdminShipmentController::class, 'create'])->middleware('permission:shipments.create');
            Route::post('/shipments/{shipment}/awb', [AdminShipmentController::class, 'awb'])->middleware('permission:shipments.awb.assign');
            Route::patch('/shipments/{shipment}/courier', [AdminShipmentController::class, 'courier'])->middleware('permission:shipments.update');
            Route::post('/shipments/{shipment}/pickup', [AdminShipmentController::class, 'pickup'])->middleware('permission:shipments.pickup.schedule');
            Route::post('/shipments/{shipment}/label', [AdminShipmentController::class, 'label'])->middleware('permission:shipments.label.generate');
            Route::post('/shipments/{shipment}/manifest', [AdminShipmentController::class, 'manifest'])->middleware('permission:shipments.manifest.generate');
            Route::post('/shipments/{shipment}/refresh-tracking', [AdminShipmentController::class, 'refresh'])->middleware('permission:shipments.tracking.refresh');
            Route::post('/shipments/{shipment}/reconcile', [AdminShipmentController::class, 'reconcile'])->middleware('permission:shipments.reconcile');
            Route::post('/shipments/{shipment}/retry', [AdminShipmentController::class, 'retry'])->middleware('permission:shipments.create');
            Route::post('/shipments/{shipment}/cancel', [AdminShipmentController::class, 'cancel'])->middleware('permission:shipments.cancel');
            Route::get('/shipments/{shipment}/documents/{type}', [AdminShipmentController::class, 'download'])->whereIn('type', ['label', 'manifest'])->middleware('permission:shipments.view');
            Route::get('/notifications/email/settings', [AdminNotificationController::class, 'settings'])->middleware('permission:notifications.settings.view');
            Route::put('/notifications/email/settings', [AdminNotificationController::class, 'updateSettings'])->middleware('permission:notifications.settings.update');
            Route::patch('/notifications/email/toggle', [AdminNotificationController::class, 'toggle'])->middleware('permission:notifications.settings.update');
            Route::post('/notifications/email/test-connection', [AdminNotificationController::class, 'testConnection'])->middleware(['permission:notifications.email.test', 'throttle:10,1']);
            Route::post('/notifications/email/send-test', [AdminNotificationController::class, 'sendTest'])->middleware(['permission:notifications.email.test', 'throttle:10,1']);
            Route::get('/notifications/templates', [AdminNotificationController::class, 'templates'])->middleware('permission:notifications.templates.view');
            Route::put('/notifications/templates/{template}', [AdminNotificationController::class, 'updateTemplate'])->middleware('permission:notifications.templates.update');
            Route::post('/notifications/templates/{template}/preview', [AdminNotificationController::class, 'preview'])->middleware('permission:notifications.templates.view');
            Route::get('/notifications/logs', [AdminNotificationController::class, 'logs'])->middleware('permission:notifications.logs.view');
            Route::get('/notifications/logs/{notificationLog}', [AdminNotificationController::class, 'log'])->middleware('permission:notifications.logs.view');
            Route::get('/notices', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'notices'])->middleware('permission:notices.view');
            Route::post('/notices', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'saveNotice'])->middleware('permission:notices.create');
            Route::get('/notices/{notice}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'notice'])->middleware('permission:notices.view');
            Route::match(['put', 'patch'], '/notices/{notice}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'saveNotice'])->middleware('permission:notices.update');
            Route::delete('/notices/{notice}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'deleteNotice'])->middleware('permission:notices.delete');
            Route::get('/gallery/albums', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'galleries'])->middleware('permission:gallery.view');
            Route::post('/gallery/albums', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'saveGallery'])->middleware('permission:gallery.create');
            Route::get('/gallery/albums/{album}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'gallery'])->middleware('permission:gallery.view');
            Route::match(['put', 'patch'], '/gallery/albums/{album}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'saveGallery'])->middleware('permission:gallery.update');
            Route::delete('/gallery/albums/{album}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'deleteGallery'])->middleware('permission:gallery.delete');
            Route::post('/gallery/albums/{album}/images', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'addImages'])->middleware('permission:gallery.images.manage');
            Route::patch('/gallery/albums/{album}/images/reorder', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'reorder'])->middleware('permission:gallery.images.reorder');
            Route::delete('/gallery/images/{image}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'deleteImage'])->middleware('permission:gallery.images.manage');
            Route::get('/videos', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'videos'])->middleware('permission:videos.view');
            Route::post('/videos', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'saveVideo'])->middleware('permission:videos.create');
            Route::match(['put', 'patch'], '/videos/{video}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'saveVideo'])->middleware('permission:videos.update');
            Route::delete('/videos/{video}', [App\Http\Controllers\Api\V1\Admin\DiscoveryController::class, 'deleteVideo'])->middleware('permission:videos.delete');
            Route::post('/notifications/logs/{notificationLog}/retry', [AdminNotificationController::class, 'retry'])->middleware('permission:notifications.logs.retry');
            Route::get('/enquiries', [OperationsController::class, 'enquiries'])->middleware('permission:enquiries.view');
            Route::get('/enquiries/{enquiry}', [OperationsController::class, 'enquiry'])->middleware('permission:enquiries.view');
            Route::patch('/enquiries/{enquiry}', [OperationsController::class, 'updateEnquiry'])->middleware('permission:enquiries.manage');
            Route::post('/enquiries/{enquiry}/notes', [OperationsController::class, 'note'])->middleware('permission:enquiries.manage');
            Route::get('/support-tickets', [OperationsController::class, 'tickets'])->middleware('permission:support-tickets.view');
            Route::get('/support-tickets/{ticket}', [OperationsController::class, 'ticket'])->middleware('permission:support-tickets.view');
            Route::patch('/support-tickets/{ticket}', [OperationsController::class, 'updateTicket'])->middleware('permission:support-tickets.manage');
            Route::post('/support-tickets/{ticket}/messages', [OperationsController::class, 'replyTicket'])->middleware('permission:support-tickets.manage');
            Route::get('/support-attachments/{attachment}', [OperationsController::class, 'supportAttachment'])->middleware(['permission:support-tickets.view', 'throttle:downloads']);
            Route::get('/workshops', [OperationsController::class, 'workshops'])->middleware('permission:workshops.view');
            Route::post('/workshops', [OperationsController::class, 'saveWorkshop'])->middleware('permission:workshops.manage');
            Route::put('/workshops/{workshop}', [OperationsController::class, 'saveWorkshop'])->middleware('permission:workshops.manage');
            Route::delete('/workshops/{workshop}', [OperationsController::class, 'deleteWorkshop'])->middleware('permission:workshops.manage');
            Route::get('/workshops/{workshop}/registrations', [OperationsController::class, 'registrations'])->middleware('permission:workshops.view');
            Route::get('/career-openings', [OperationsController::class, 'jobs'])->middleware('permission:careers.view');
            Route::get('/internship-certificates', [AdminInternshipCertificateController::class, 'index'])->middleware('permission:careers.view');
            Route::post('/internship-certificates', [AdminInternshipCertificateController::class, 'store'])->middleware('permission:careers.manage');
            Route::patch('/internship-certificates/{certificate}', [AdminInternshipCertificateController::class, 'update'])->middleware('permission:careers.manage');
            Route::delete('/internship-certificates/{certificate}', [AdminInternshipCertificateController::class, 'destroy'])->middleware('permission:careers.manage');
            Route::post('/career-openings', [OperationsController::class, 'saveJob'])->middleware('permission:careers.manage');
            Route::put('/career-openings/{job}', [OperationsController::class, 'saveJob'])->middleware('permission:careers.manage');
            Route::delete('/career-openings/{job}', [OperationsController::class, 'deleteJob'])->middleware('permission:careers.manage');
            Route::get('/career-applications', [OperationsController::class, 'applications'])->middleware('permission:careers.view');
            Route::patch('/career-applications/{application}', [OperationsController::class, 'updateApplication'])->middleware('permission:careers.manage');
            Route::get('/career-applications/{application}/resume', [OperationsController::class, 'resume'])->middleware(['permission:careers.view', 'throttle:downloads']);
            Route::get('/entitlements', [EntitlementController::class, 'index']);
            Route::post('/entitlements', [EntitlementController::class, 'store']);
            Route::get('/entitlements/{entitlement}', [EntitlementController::class, 'show']);
            Route::patch('/entitlements/{entitlement}/revoke', [EntitlementController::class, 'revoke']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::get('/users/{user}', [AdminUserController::class, 'show']);
            Route::match(['put', 'patch'], '/users/{user}', [AdminUserController::class, 'update']);
            Route::patch('/users/{user}/status', [AdminUserController::class, 'status']);

            Route::get('/roles', [RoleController::class, 'index']);
            Route::post('/roles', [RoleController::class, 'store']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);
            Route::patch('/roles/{role}', [RoleController::class, 'update']);
            Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);

            Route::get('/permissions', PermissionController::class);

            Route::get('/pages', [CmsPageController::class, 'index']);
            Route::post('/pages', [CmsPageController::class, 'store']);
            Route::get('/pages/{page}', [CmsPageController::class, 'show']);
            Route::match(['put', 'patch'], '/pages/{page}', [CmsPageController::class, 'update']);
            Route::patch('/pages/{page}/status', [CmsPageController::class, 'status']);
            Route::delete('/pages/{page}', [CmsPageController::class, 'destroy']);
            Route::get('/pages/{page}/sections', [PageSectionController::class, 'index']);
            Route::post('/pages/{page}/sections', [PageSectionController::class, 'store']);
            Route::patch('/pages/{page}/sections/reorder', [PageSectionController::class, 'reorder']);
            Route::match(['put', 'patch'], '/page-sections/{section}', [PageSectionController::class, 'update']);
            Route::delete('/page-sections/{section}', [PageSectionController::class, 'destroy']);
            Route::get('/pages/{page}/seo', [SeoController::class, 'show']);
            Route::put('/pages/{page}/seo', [SeoController::class, 'update']);
            Route::get('/settings', [SettingsController::class, 'index']);
            Route::put('/settings', [SettingsController::class, 'update']);
            Route::get('/social-links', [SocialLinkController::class, 'index']);
            Route::post('/social-links', [SocialLinkController::class, 'store']);
            Route::patch('/social-links/reorder', [SocialLinkController::class, 'reorder']);
            Route::match(['put', 'patch'], '/social-links/{socialLink}', [SocialLinkController::class, 'update']);
            Route::patch('/social-links/{socialLink}/status', [SocialLinkController::class, 'status']);
            Route::delete('/social-links/{socialLink}', [SocialLinkController::class, 'destroy']);
            Route::get('/banners', [BannerController::class, 'index']);
            Route::post('/banners', [BannerController::class, 'store']);
            Route::patch('/banners/reorder', [BannerController::class, 'reorder']);
            Route::get('/banners/{banner}', [BannerController::class, 'show']);
            Route::match(['put', 'patch'], '/banners/{banner}', [BannerController::class, 'update']);
            Route::patch('/banners/{banner}/status', [BannerController::class, 'status']);
            Route::delete('/banners/{banner}', [BannerController::class, 'destroy']);
            Route::get('/media', [MediaController::class, 'index']);
            Route::post('/media', [MediaController::class, 'store']);
            Route::get('/media/{media}', [MediaController::class, 'show']);
            Route::patch('/media/{media}', [MediaController::class, 'update']);
            Route::delete('/media/{media}', [MediaController::class, 'destroy']);
            Route::get('/redirects', [RedirectController::class, 'index']);
            Route::post('/redirects', [RedirectController::class, 'store']);
            Route::match(['put', 'patch'], '/redirects/{redirect}', [RedirectController::class, 'update']);
            Route::delete('/redirects/{redirect}', [RedirectController::class, 'destroy']);
            Route::get('/contributors', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'index']);
            Route::post('/contributors', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'store']);
            Route::patch('/contributors/reorder', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'reorder']);
            Route::get('/contributors/{contributor}', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'show']);
            Route::match(['put', 'patch'], '/contributors/{contributor}', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'update']);
            Route::patch('/contributors/{contributor}/status', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'status']);
            Route::patch('/contributors/{contributor}/featured', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'featured']);
            Route::delete('/contributors/{contributor}', [App\Http\Controllers\Api\V1\Admin\ContributorController::class, 'destroy']);
            Route::get('/contributors/{contributor}/seo', [ContributorSeoController::class, 'show']);
            Route::put('/contributors/{contributor}/seo', [ContributorSeoController::class, 'update']);
            Route::get('/articles', [App\Http\Controllers\Api\V1\Admin\ArticleController::class, 'index']);
            Route::post('/articles', [App\Http\Controllers\Api\V1\Admin\ArticleController::class, 'store']);
            Route::get('/articles/{article}', [App\Http\Controllers\Api\V1\Admin\ArticleController::class, 'show']);
            Route::match(['put', 'patch'], '/articles/{article}', [App\Http\Controllers\Api\V1\Admin\ArticleController::class, 'update']);
            Route::patch('/articles/{article}/status', [App\Http\Controllers\Api\V1\Admin\ArticleController::class, 'status']);
            Route::patch('/articles/{article}/featured', [App\Http\Controllers\Api\V1\Admin\ArticleController::class, 'featured']);
            Route::delete('/articles/{article}', [App\Http\Controllers\Api\V1\Admin\ArticleController::class, 'destroy']);
            Route::get('/articles/{article}/seo', [ArticleSeoController::class, 'show']);
            Route::put('/articles/{article}/seo', [ArticleSeoController::class, 'update']);
            Route::get('/courses', [App\Http\Controllers\Api\V1\Admin\CourseController::class, 'index']);
            Route::post('/courses', [App\Http\Controllers\Api\V1\Admin\CourseController::class, 'store']);
            Route::get('/courses/{course}', [App\Http\Controllers\Api\V1\Admin\CourseController::class, 'show']);
            Route::match(['put', 'patch'], '/courses/{course}', [App\Http\Controllers\Api\V1\Admin\CourseController::class, 'update']);
            Route::patch('/courses/{course}/status', [App\Http\Controllers\Api\V1\Admin\CourseController::class, 'status']);
            Route::patch('/courses/{course}/featured', [App\Http\Controllers\Api\V1\Admin\CourseController::class, 'featured']);
            Route::delete('/courses/{course}', [App\Http\Controllers\Api\V1\Admin\CourseController::class, 'destroy']);
            Route::get('/courses/{course}/seo', [CourseSeoController::class, 'show']);
            Route::put('/courses/{course}/seo', [CourseSeoController::class, 'update']);
            Route::get('/publications', [App\Http\Controllers\Api\V1\Admin\PublicationController::class, 'index']);
            Route::post('/publications', [App\Http\Controllers\Api\V1\Admin\PublicationController::class, 'store']);
            Route::get('/publications/{publication}', [App\Http\Controllers\Api\V1\Admin\PublicationController::class, 'show']);
            Route::match(['put', 'patch'], '/publications/{publication}', [App\Http\Controllers\Api\V1\Admin\PublicationController::class, 'update']);
            Route::patch('/publications/{publication}/status', [App\Http\Controllers\Api\V1\Admin\PublicationController::class, 'status']);
            Route::patch('/publications/{publication}/featured', [App\Http\Controllers\Api\V1\Admin\PublicationController::class, 'featured']);
            Route::delete('/publications/{publication}', [App\Http\Controllers\Api\V1\Admin\PublicationController::class, 'destroy']);
            Route::get('/publications/{p}/seo', [PublicationSeoController::class, 'show']);
            Route::put('/publications/{p}/seo', [PublicationSeoController::class, 'update']);
            Route::get('/resources', [App\Http\Controllers\Api\V1\Admin\DigitalResourceController::class, 'index']);
            Route::post('/resources', [App\Http\Controllers\Api\V1\Admin\DigitalResourceController::class, 'store']);
            Route::get('/resources/{resource}', [App\Http\Controllers\Api\V1\Admin\DigitalResourceController::class, 'show']);
            Route::match(['put', 'patch'], '/resources/{resource}', [App\Http\Controllers\Api\V1\Admin\DigitalResourceController::class, 'update']);
            Route::patch('/resources/{resource}/status', [App\Http\Controllers\Api\V1\Admin\DigitalResourceController::class, 'status']);
            Route::patch('/resources/{resource}/featured', [App\Http\Controllers\Api\V1\Admin\DigitalResourceController::class, 'featured']);
            Route::delete('/resources/{resource}', [App\Http\Controllers\Api\V1\Admin\DigitalResourceController::class, 'destroy']);
            Route::get('/resources/{resource}/seo', [DigitalResourceSeoController::class, 'show']);
            Route::put('/resources/{resource}/seo', [DigitalResourceSeoController::class, 'update']);
            Route::get('/books', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'index']);
            Route::post('/books', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'store']);
            Route::get('/books/{book}', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'show']);
            Route::match(['put', 'patch'], '/books/{book}', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'update']);
            Route::patch('/books/{book}/status', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'status']);
            Route::patch('/books/{book}/featured', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'featured']);
            Route::patch('/books/{book}/new-arrival', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'newArrival']);
            Route::put('/books/{book}/gallery', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'gallery']);
            Route::delete('/books/{book}', [App\Http\Controllers\Api\V1\Admin\BookController::class, 'destroy']);
            Route::get('/books/{book}/seo', [BookSeoController::class, 'show']);
            Route::put('/books/{book}/seo', [BookSeoController::class, 'update']);
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::get('/books/{book}/inventory', [InventoryController::class, 'show']);
            Route::post('/books/{book}/inventory/increase', [InventoryController::class, 'increase']);
            Route::post('/books/{book}/inventory/decrease', [InventoryController::class, 'decrease']);
            Route::post('/books/{book}/inventory/adjust', [InventoryController::class, 'adjust']);
            Route::patch('/books/{book}/inventory/threshold', [InventoryController::class, 'threshold']);
            Route::get('/books/{book}/inventory/movements', [InventoryController::class, 'movements']);
            Route::apiResource('authors', App\Http\Controllers\Api\V1\Admin\AuthorController::class);
            Route::apiResource('publishers', App\Http\Controllers\Api\V1\Admin\PublisherController::class);
            Route::get('/coupons', [CouponController::class, 'index']);
            Route::post('/coupons', [CouponController::class, 'store']);
            Route::get('/coupons/{coupon}', [CouponController::class, 'show']);
            Route::match(['put', 'patch'], '/coupons/{coupon}', [CouponController::class, 'update']);
            Route::patch('/coupons/{coupon}/status', [CouponController::class, 'status']);
            Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);
            Route::get('/shipping/providers', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'providers']);
            Route::get('/shipping/providers/{provider}', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'provider']);
            Route::put('/shipping/providers/{provider}/credentials', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'credentials']);
            Route::delete('/shipping/providers/{provider}/credentials', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'clearCredentials']);
            Route::patch('/shipping/providers/{provider}/toggle', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'toggle']);
            Route::post('/shipping/providers/{provider}/test-connection', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'test']);
            Route::patch('/shipping/providers/{provider}/default', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'makeDefault']);
            Route::patch('/shipping/providers/{provider}/priority', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'priority']);
            Route::get('/shipping/settings', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'settings']);
            Route::put('/shipping/settings', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'updateSettings']);
            Route::get('/shipping/pickup-locations', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'pickups']);
            Route::post('/shipping/pickup-locations', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'savePickup']);
            Route::put('/shipping/pickup-locations/{pickup}', [App\Http\Controllers\Api\V1\Admin\ShippingController::class, 'savePickup']);

            $adminMasterControllers = [
                'engineering-disciplines' => App\Http\Controllers\Api\V1\Admin\EngineeringDisciplineController::class,
                'categories' => App\Http\Controllers\Api\V1\Admin\CategoryController::class,
                'topics' => App\Http\Controllers\Api\V1\Admin\TopicController::class,
                'tags' => App\Http\Controllers\Api\V1\Admin\TagController::class,
                'course-levels' => App\Http\Controllers\Api\V1\Admin\CourseLevelController::class,
                'resource-types' => App\Http\Controllers\Api\V1\Admin\ResourceTypeController::class,
                'publication-types' => App\Http\Controllers\Api\V1\Admin\PublicationTypeController::class,
            ];
            foreach ($adminMasterControllers as $path => $controller) {
                Route::get("/{$path}", [$controller, 'index'])->defaults('master_type', $path);
                Route::post("/{$path}", [$controller, 'store'])->defaults('master_type', $path);
                if ($path !== 'tags') {
                    Route::patch("/{$path}/reorder", [$controller, 'reorder'])->defaults('master_type', $path);
                }
                Route::get("/{$path}/{id}", [$controller, 'show'])->whereNumber('id')->defaults('master_type', $path);
                Route::match(['put', 'patch'], "/{$path}/{id}", [$controller, 'update'])->whereNumber('id')->defaults('master_type', $path);
                Route::patch("/{$path}/{id}/status", [$controller, 'status'])->whereNumber('id')->defaults('master_type', $path);
            }
        });
    });
});

Route::fallback(fn () => response()->json([
    'success' => false,
    'message' => 'API endpoint not found.',
    'errors' => (object) [],
], 404));
