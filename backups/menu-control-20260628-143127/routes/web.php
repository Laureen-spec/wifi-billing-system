<?php



use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InternetPackageController;
use App\Http\Controllers\IspController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MikrotikRouterController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProvisioningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\BankTransferPaymentController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\CouponController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\HelpdeskCategoryController;
use App\Http\Controllers\HelpdeskTicketController;
use App\Http\Controllers\HelpdeskReplyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SalesProposalController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\AIAgentChatPageController;
use App\Http\Controllers\AIAgentChatController;
use Inertia\Inertia;
use StudyRoomTechLab\LandingPage\Http\Controllers\LandingPageController as PublicLandingPageController;

Route::get('/', function (\Illuminate\Http\Request $request) {
    if (class_exists(PublicLandingPageController::class) && function_exists('isLandingPageEnabled') && isLandingPageEnabled()) {
        return app(PublicLandingPageController::class)->index($request);
    }

    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('landing.page');

Route::get('/provision/{token}/heartbeat', [ProvisioningController::class, 'heartbeat'])->name('provision.heartbeat');
Route::get('/provision/{token}', [ProvisioningController::class, 'show'])->name('provision.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/billing-dashboard', [DashboardController::class, 'index'])->name('billing.dashboard');

    Route::resource('isps', IspController::class)->except(['show', 'destroy']);

    Route::get('/isp/packages', [InternetPackageController::class, 'index'])->name('isp.packages.index');
    Route::get('/isp/packages/create', [InternetPackageController::class, 'create'])->name('isp.packages.create');
    Route::post('/isp/packages', [InternetPackageController::class, 'store'])->name('isp.packages.store');
    Route::get('/isp/packages/{package}/edit', [InternetPackageController::class, 'edit'])->name('isp.packages.edit');
    Route::patch('/isp/packages/{package}', [InternetPackageController::class, 'update'])->name('isp.packages.update');

    Route::get('/isp/customers', [CustomerController::class, 'index'])->name('isp.customers.index');
    Route::get('/isp/customers/create', [CustomerController::class, 'create'])->name('isp.customers.create');
    Route::post('/isp/customers', [CustomerController::class, 'store'])->name('isp.customers.store');
    Route::get('/isp/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('isp.customers.edit');
    Route::patch('/isp/customers/{customer}', [CustomerController::class, 'update'])->name('isp.customers.update');

    Route::get('/isp/routers', [MikrotikRouterController::class, 'index'])->name('isp.routers.index');
    Route::get('/isp/routers/create', [MikrotikRouterController::class, 'create'])->name('isp.routers.create');
    Route::post('/isp/routers', [MikrotikRouterController::class, 'store'])->name('isp.routers.store');
    Route::get('/isp/routers/{router}', [MikrotikRouterController::class, 'show'])->name('isp.routers.show');
    Route::get('/isp/routers/{router}/edit', [MikrotikRouterController::class, 'edit'])->name('isp.routers.edit');
    Route::patch('/isp/routers/{router}', [MikrotikRouterController::class, 'update'])->name('isp.routers.update');
    Route::get('/isp/routers/{router}/setup-script', [MikrotikRouterController::class, 'setupScript'])->name('isp.routers.setup-script');
    Route::get('/isp/routers/{router}/live', [MikrotikRouterController::class, 'live'])->name('isp.routers.live');
    Route::post('/isp/routers/{router}/test', [MikrotikRouterController::class, 'test'])->name('isp.routers.test');
    Route::post('/isp/routers/{router}/regenerate-winbox', [MikrotikRouterController::class, 'regenerateWinbox'])->name('isp.routers.regenerate-winbox');
    Route::post('/isp/routers/{router}/enable-winbox', [MikrotikRouterController::class, 'enableWinbox'])->name('isp.routers.enable-winbox');
    Route::post('/isp/routers/{router}/reprovision', [MikrotikRouterController::class, 'reprovision'])->name('isp.routers.reprovision');
    Route::post('/isp/routers/{router}/sync-hotspot', [MikrotikRouterController::class, 'syncHotspot'])->name('isp.routers.sync-hotspot');
    Route::post('/isp/routers/{router}/sync-time', [MikrotikRouterController::class, 'syncTime'])->name('isp.routers.sync-time');
    Route::delete('/isp/routers/{router}', [MikrotikRouterController::class, 'destroy'])->name('isp.routers.destroy');

    Route::get('/isp/provisioning', [ProvisioningController::class, 'index'])->name('isp.provisioning.index');
    Route::post('/isp/provisioning/generate', [ProvisioningController::class, 'generate'])->name('isp.provisioning.generate');
    Route::get('/isp/provisioning/{token}', [ProvisioningController::class, 'details'])->name('isp.provisioning.show');
    Route::post('/isp/provisioning/{token}/deactivate', [ProvisioningController::class, 'deactivate'])->name('isp.provisioning.deactivate');
});


Route::middleware(['auth', 'verified', 'PlanModuleCheck'])->group(function () {
    // Route::get('/dashboard', function () {
    //     return Inertia::render('dashboard');
    // })->name('dashboard');

    Route::get('dashboard', [HomeController::class, 'Dashboard'])->name('dashboard');

    // Profile management routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource management routes
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.change-password');
    Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    Route::post('users/leave-impersonation', [UserController::class, 'leaveImpersonation'])->name('users.leave-impersonation');
    Route::get('users/login/history', [UserController::class, 'loginHistory'])->name('users.login-history');
    Route::get('users/{user}/admin-hub', [UserController::class, 'adminHub'])->name('users.admin-hub');
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/assign-plan', [UserController::class, 'assignPlan'])->name('users.assign-plan');
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('transfers', TransferController::class)->except(['edit', 'update']);

    Route::resource('roles', RoleController::class);

    // purchase invoices
    Route::resource('purchase-invoices', PurchaseInvoiceController::class);
    Route::post('purchase-invoices/{purchaseInvoice}/post', [PurchaseInvoiceController::class, 'post'])->name('purchase-invoices.post');
    Route::get('purchase-invoices/{purchaseInvoice}/print', [PurchaseInvoiceController::class, 'print'])->name('purchase-invoices.print');

    // sales invoices
    Route::resource('sales-invoices', SalesInvoiceController::class);
    Route::post('sales-invoices/{salesInvoice}/post', [SalesInvoiceController::class, 'post'])->name('sales-invoices.post');
    Route::get('sales-invoices/{salesInvoice}/print', [SalesInvoiceController::class, 'print'])->name('sales-invoices.print');
    Route::get('sales-invoices/warehouse/products', [SalesInvoiceController::class, 'getWarehouseProducts'])->name('sales-invoices.warehouse.products');
    Route::get('sales-invoices/services/list', [SalesInvoiceController::class, 'getServices'])->name('sales-invoices.services');

    // purchase returns
    Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])->name('purchase-returns.index');
    Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
    Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    Route::get('purchase-returns/{return}', [PurchaseReturnController::class, 'show'])->name('purchase-returns.show');
    Route::delete('purchase-returns/{return}', [PurchaseReturnController::class, 'destroy'])->name('purchase-returns.destroy');
    Route::post('purchase-returns/{return}/approve', [PurchaseReturnController::class, 'approve'])->name('purchase-returns.approve');
    Route::post('purchase-returns/{return}/complete', [PurchaseReturnController::class, 'complete'])->name('purchase-returns.complete');

    // sales returns
    Route::get('sales-returns', [SalesReturnController::class, 'index'])->name('sales-returns.index');
    Route::get('sales-returns/create', [SalesReturnController::class, 'create'])->name('sales-returns.create');
    Route::post('sales-returns', [SalesReturnController::class, 'store'])->name('sales-returns.store');
    Route::get('sales-returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('sales-returns.show');
    Route::delete('sales-returns/{salesReturn}', [SalesReturnController::class, 'destroy'])->name('sales-returns.destroy');
    Route::post('sales-returns/{salesReturn}/approve', [SalesReturnController::class, 'approve'])->name('sales-returns.approve');
    Route::post('sales-returns/{salesReturn}/complete', [SalesReturnController::class, 'complete'])->name('sales-returns.complete');

    // Helpdesk Routes
    Route::resource('helpdesk-categories', HelpdeskCategoryController::class);
    Route::get('helpdesk-ticket/today', [HelpdeskTicketController::class, 'today'])->name('helpdesk-tickets.today');
    Route::resource('helpdesk-tickets', HelpdeskTicketController::class);

    // Helpdesk Replies (AJAX endpoints)
    Route::post('helpdesk-tickets/{ticket}/replies', [HelpdeskReplyController::class, 'store'])->name('helpdesk-replies.store');
    Route::delete('helpdesk-replies/{reply}', [HelpdeskReplyController::class, 'destroy'])->name('helpdesk-replies.destroy');
    Route::resource('plans', PlanController::class);
    Route::resource('coupons', CouponController::class);
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::get('plans/{plan}/subscribe', [PlanController::class, 'subscribe'])->name('plans.subscribe');
    Route::post('plans/{plan}/start-trial', [PlanController::class, 'startTrial'])->name('plans.start-trial');
    Route::post('plans/add-on/update-price', [PlanController::class, 'updateModulePrice'])->name('plans.add-on.update-price');
    Route::post('plans/apply-coupon', [PlanController::class, 'applyCoupon'])->name('plans.apply-coupon');
    Route::post('plans/{plan}/assign-free', [PlanController::class, 'assignFreePlan'])->name('plans.assign-free');
    Route::post('plans/package-settings', [PlanController::class, 'updatePackageSettings'])->name('plans.package-settings.update');
    Route::post('subscriptions', [PlanController::class, 'store'])->name('subscriptions.store');

    // Add-on management routes
    Route::get('add-ons', [ModuleController::class, 'index'])->name('add-ons.index');
    Route::get('add-on/upload', [ModuleController::class, 'upload'])->name('add-on.upload');
    Route::post('add-ons/install', [ModuleController::class, 'install'])->name('add-ons.install');
    Route::post('add-on/{name}/enable', [ModuleController::class, 'enable'])->name('add-on.enable');
    Route::get('user/active-modules', [ModuleController::class, 'getUserActiveModules'])->name('user.active-modules');
    Route::delete('user/active-modules/{moduleId}', [ModuleController::class, 'removeUserActiveModule'])->name('user.active-modules.remove');

    // Settings management routes
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/brand', [SettingController::class, 'updateBrandSettings'])->name('settings.brand.update');
    Route::post('settings/company', [SettingController::class, 'updateCompanySettings'])->name('settings.company.update');
    Route::post('settings/system', [SettingController::class, 'updateSystemSettings'])->name('settings.system.update');
    Route::post('settings/currency', [SettingController::class, 'updateCurrencySettings'])->name('settings.currency.update');
    Route::post('settings/cache/clear', [SettingController::class, 'clearCache'])->name('settings.cache.clear');
    Route::post('settings/optimize', [SettingController::class, 'optimizeSite'])->name('settings.optimize');
    Route::post('settings/cookie', [SettingController::class, 'updateCookieSettings'])->name('settings.cookie.update');
    Route::post('settings/seo', [SettingController::class, 'updateSeoSettings'])->name('settings.seo.update');
    Route::post('settings/storage', [SettingController::class, 'updateStorageSettings'])->name('settings.storage.update');
    Route::get('settings/cookie/download', [SettingController::class, 'downloadCookieData'])->name('settings.cookie.download');
    Route::post('settings/email', [SettingController::class, 'updateEmailSettings'])->name('settings.email.update');
    Route::post('settings/email/test', [SettingController::class, 'testEmail'])->name('settings.email.test');
    Route::post('settings/pusher', [SettingController::class, 'updatePusherSettings'])->name('settings.pusher.update');
    Route::post('settings/bank-transfer', [SettingController::class, 'updateBankTransferSettings'])->name('settings.bank-transfer.update');
    Route::post('settings/ai-agent', [SettingController::class, 'updateAIAgentSettings'])->name('settings.ai-agent.update');
    Route::get('settings/ai-agent/providers', [SettingController::class, 'getAIAgentProviders'])->name('settings.ai-agent.providers');
    Route::post('email-notification-settings-save', [SettingController::class, 'mailNotificationStore'])->name('email.notification.setting.store');

    // Bank Transfer Payment routes
    Route::post('bank-transfer', [BankTransferPaymentController::class, 'store'])->name('payment.bank-transfer.store');
    Route::get('bank-transfer', [BankTransferPaymentController::class, 'index'])->name('bank-transfer.index');
    Route::post('bank-transfer/update/{id}', [BankTransferPaymentController::class, 'update'])->name('bank-transfer.update');
    Route::post('bank-transfer/{payment}/reject', [BankTransferPaymentController::class, 'reject'])->name('bank-transfer.reject');
    Route::delete('bank-transfer/{payment}', [BankTransferPaymentController::class, 'destroy'])->name('bank-transfer.destroy');

    // Language management routes
    Route::get('/languages/manage', [TranslationController::class, 'manage'])->name('languages.manage');
    Route::post('/languages/{locale}/update', [TranslationController::class, 'updateTranslations'])->name('languages.update');
    Route::get('/languages/{locale}/package/{packageName}', [TranslationController::class, 'getPackageTranslations'])->name('languages.package.translations');
    Route::post('/languages/{locale}/package/{packageName}/update', [TranslationController::class, 'updatePackageTranslations'])->name('languages.package.update');
    Route::post('/languages/create', [TranslationController::class, 'createLanguage'])->name('languages.create');
    Route::delete('/languages/{languageCode}', [TranslationController::class, 'deleteLanguage'])->name('languages.delete');
    Route::patch('/languages/{languageCode}/toggle', [TranslationController::class, 'toggleLanguageStatus'])->name('languages.toggle');
    Route::post('/languages/change', [TranslationController::class, 'changeLanguage'])->name('languages.change');

    // Email templates routes
    Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    Route::get('email-templates/{emailTemplate}/language/{lang}', [EmailTemplateController::class, 'getLanguageContent'])->name('email-templates.language-content');
    Route::put('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::put('email-templates/{emailTemplate}/update-meta', [EmailTemplateController::class, 'updateMeta'])->name('email-templates.update-meta');

    // Notification template routes
    Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
    Route::get('notification-templates/{notificationTemplate}/edit', [NotificationTemplateController::class, 'edit'])->name('notification-templates.edit');
    Route::get('notification-templates/{notificationTemplate}/language/{lang}', [NotificationTemplateController::class, 'getLanguageContent'])->name('notification-templates.language-content');
    Route::put('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'update'])->name('notification-templates.update');

     // Proposal Routes
    Route::resource('sales-proposals', SalesProposalController::class);
    Route::get('sales-proposals/{salesProposal}/print', [SalesProposalController::class, 'print'])->name('sales-proposals.print');
    Route::post('sales-proposals/{salesProposal}/sent', [SalesProposalController::class, 'sent'])->name('sales-proposals.sent');
    Route::post('sales-proposals/{salesProposal}/accept', [SalesProposalController::class, 'accept'])->name('sales-proposals.accept');
    Route::post('sales-proposals/{salesProposal}/reject', [SalesProposalController::class, 'reject'])->name('sales-proposals.reject');
    Route::post('sales-proposals/{salesProposal}/convert-to-invoice', [SalesProposalController::class, 'convertToInvoice'])->name('sales-proposals.convert-to-invoice');
    Route::get('sales-proposals/warehouse/products', [SalesProposalController::class, 'getWarehouseProducts'])->name('sales-proposals.warehouse.products');
    Route::get('sales-proposals/services/list', [SalesProposalController::class, 'getServices'])->name('sales-proposals.services');

    // Messenger routes
    Route::get('messenger', [MessengerController::class, 'index'])->name('messenger.index');
    Route::post('messenger/send', [MessengerController::class, 'send'])->name('messenger.send');
    Route::get('messenger/contacts', [MessengerController::class, 'getContacts'])->name('messenger.contacts');
    Route::get('messenger/messages/{userId}', [MessengerController::class, 'getMessages'])->name('messenger.messages');
    Route::post('messenger/toggle-favorite', [MessengerController::class, 'toggleFavorite'])->name('messenger.toggle-favorite');
    Route::get('messenger/favorites', [MessengerController::class, 'getFavorites'])->name('messenger.favorites');
    Route::put('messenger/messages/{messageId}/edit', [MessengerController::class, 'editMessage'])->name('messenger.edit-message');
    Route::delete('messenger/messages/{messageId}', [MessengerController::class, 'deleteMessage'])->name('messenger.delete-message');
    Route::post('/messenger/set-offline', [MessengerController::class, 'setOffline'])->name('messenger.set-offline');
    Route::post('/messenger/update-presence', [MessengerController::class, 'updatePresence'])->name('messenger.update-presence');
    Route::get('/messenger/online-users', [MessengerController::class, 'getOnlineUsers'])->name('messenger.online-users');
    Route::post('/messenger/toggle-pin', [MessengerController::class, 'togglePin'])->name('messenger.toggle-pin');
    Route::get('/messenger/pinned', [MessengerController::class, 'getPinned'])->name('messenger.pinned');
    Route::get('/messenger/check-new-messages', [MessengerController::class, 'checkNewMessages'])->name('messenger.check-new-messages');

    // AI Agent routes
    Route::prefix('ai-agent')->name('ai-agent.')->group(function () {
        Route::get('/chat-page', [AIAgentChatPageController::class, 'index'])->name('chat.page');
        Route::post('/chat', [AIAgentChatController::class, 'chat'])->name('chat');
        Route::get('/sessions', [AIAgentChatPageController::class, 'getSessions'])->name('sessions.index');
        Route::post('/sessions', [AIAgentChatPageController::class, 'createSession'])->name('sessions.store');
        Route::delete('/sessions/{session}', [AIAgentChatPageController::class, 'destroySession'])->name('sessions.destroy');
        Route::get('/sessions/{session}/messages', [AIAgentChatPageController::class, 'getMessages'])->name('sessions.messages');
    });

    // Media Library API routes
    Route::get('media-library', [MediaController::class, 'page'])->name('media-library');
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media/batch', [MediaController::class, 'batchStore'])->name('media.batch');
    Route::delete('media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('media/directories', [MediaController::class, 'createDirectory'])->name('media.directories.create');
    Route::put('media/directories/{id}', [MediaController::class, 'updateDirectory'])->name('media.directories.update');
    Route::delete('media/directories/{id}', [MediaController::class, 'destroyDirectory'])->name('media.directories.destroy');
    Route::patch('media/{id}/directory', [MediaController::class, 'updateMediaDirectory'])->name('media.directory.update');
});

Route::get('/storage-link', function () {
    Artisan::call('storage:unlink');
    Artisan::call('storage:link');
    return 'done';
});

Route::get('/translations/{locale}', [TranslationController::class, 'getTranslations'])->name('languages.translations');
Route::post('/cookie-consent-log', [SettingController::class, 'logCookieConsent'])->name('cookie.consent.log');

//for Instagramchat & Facebookchat
Route::any('/meta/callback', [MetaController::class, 'handleWebhook'])->name('meta.callback');


require __DIR__.'/installer.php';
require __DIR__.'/updater.php';
require __DIR__.'/auth.php';
