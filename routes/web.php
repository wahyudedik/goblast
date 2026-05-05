<?php

use App\Http\Controllers\Admin\AlertController as AdminAlertController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GatewayController as AdminGatewayController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\SystemConfigController as AdminSystemConfigController;
use App\Http\Controllers\Admin\SystemLogController as AdminSystemLogController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\ApiDocController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\KeywordRuleController;
use App\Http\Controllers\MessageLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WebhookController;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

    return view('welcome', ['plans' => $plans]);
});

// Static pages
Route::view('/features', 'pages.features')->name('pages.features');
Route::redirect('/pricing', '/#pricing')->name('pages.pricing');
Route::view('/about', 'pages.about')->name('pages.about');
Route::view('/contact', 'pages.contact')->name('pages.contact');
Route::view('/faq', 'pages.faq')->name('pages.faq');
Route::view('/privacy', 'pages.privacy')->name('pages.privacy');
Route::view('/terms', 'pages.terms')->name('pages.terms');

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'superadmin') {
        return redirect()->route('admin.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Authenticated tenant routes (requires email verification)
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Device management routes
    Route::get('devices/rate-limit-status', [DeviceController::class, 'rateLimitStatus'])->name('devices.rate-limit-status');
    Route::resource('devices', DeviceController::class)->except(['store']);
    Route::post('devices', [DeviceController::class, 'store'])->name('devices.store')->middleware('throttle:device-creation');
    Route::get('devices/{device}/connect', [DeviceController::class, 'connect'])->name('devices.connect');
    Route::post('devices/{device}/disconnect', [DeviceController::class, 'disconnect'])->name('devices.disconnect');
    Route::get('devices/{device}/check-status', [DeviceController::class, 'checkStatus'])->name('devices.check-status');
    Route::get('devices/{device}/qr-code', [DeviceController::class, 'getQrCode'])->name('devices.qr-code');

    // Template management routes
    Route::resource('templates', TemplateController::class);
    Route::post('templates/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('templates.duplicate');

    // Broadcast management routes
    Route::resource('broadcasts', BroadcastController::class)->except(['edit', 'update']);
    Route::post('broadcasts/{broadcast}/dispatch', [BroadcastController::class, 'dispatch'])->name('broadcasts.dispatch');
    Route::post('broadcasts/{broadcast}/cancel', [BroadcastController::class, 'cancel'])->name('broadcasts.cancel');
    Route::post('broadcasts/{broadcast}/retry-failed', [BroadcastController::class, 'retryFailed'])->name('broadcasts.retry-failed');
    Route::get('broadcasts/{broadcast}/progress', [BroadcastController::class, 'progress'])->name('broadcasts.progress');

    // Reminder management routes
    Route::resource('reminders', ReminderController::class);
    Route::post('reminders/{reminder}/toggle', [ReminderController::class, 'toggle'])->name('reminders.toggle');

    // Contact management routes
    Route::resource('contacts', ContactController::class)->except(['show']);
    Route::post('contacts/import', [ContactController::class, 'import'])->name('contacts.import');
    Route::get('api/contacts/search', [ContactController::class, 'apiSearch'])->name('api.contacts.search');
    Route::get('api/contacts/by-group', [ContactController::class, 'apiByGroup'])->name('api.contacts.by-group');

    // Auto Reply (Keyword Rules) routes
    Route::get('auto-reply', [KeywordRuleController::class, 'index'])->name('auto-reply.index');
    Route::get('auto-reply/create', [KeywordRuleController::class, 'create'])->name('auto-reply.create');
    Route::post('auto-reply', [KeywordRuleController::class, 'store'])->name('auto-reply.store');
    Route::get('auto-reply/{keywordRule}/edit', [KeywordRuleController::class, 'edit'])->name('auto-reply.edit');
    Route::put('auto-reply/{keywordRule}', [KeywordRuleController::class, 'update'])->name('auto-reply.update');
    Route::post('auto-reply/{keywordRule}/toggle', [KeywordRuleController::class, 'toggle'])->name('auto-reply.toggle');
    Route::delete('auto-reply/{keywordRule}', [KeywordRuleController::class, 'destroy'])->name('auto-reply.destroy');

    // Message log routes
    Route::get('message-logs', [MessageLogController::class, 'index'])->name('message-logs.index');
    Route::get('message-logs/export', [MessageLogController::class, 'export'])->name('message-logs.export');
    Route::get('message-logs/{messageLog}', [MessageLogController::class, 'show'])->name('message-logs.show');
    Route::post('message-logs/{messageLog}/retry', [MessageLogController::class, 'retry'])->name('message-logs.retry');
    Route::delete('message-logs/{messageLog}', [MessageLogController::class, 'destroy'])->name('message-logs.destroy');

    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('notifications', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

    // API routes for notifications (AJAX)
    Route::get('api/notifications', [NotificationController::class, 'apiIndex'])->name('api.notifications.index');
    Route::post('api/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all-read');
    Route::post('api/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.mark-read');

    // Subscription routes
    Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('subscription/plans', [SubscriptionController::class, 'plans'])->name('subscription.plans');

    // API Token management routes
    Route::resource('api-tokens', ApiTokenController::class)->except(['edit', 'update']);
    Route::post('api-tokens/{apiToken}/revoke', [ApiTokenController::class, 'revoke'])->name('api-tokens.revoke');

    // API Documentation route
    Route::get('api-docs', ApiDocController::class)->name('api-docs.index');
});

// Superadmin routes
Route::middleware(['auth', 'verified', 'role:superadmin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    // Tenant management
    Route::resource('tenants', AdminTenantController::class);
    Route::post('tenants/{tenant}/suspend', [AdminTenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('tenants/{tenant}/activate', [AdminTenantController::class, 'activate'])->name('tenants.activate');
    Route::post('tenants/{tenant}/extend-trial', [AdminTenantController::class, 'extendTrial'])->name('tenants.extend-trial');

    // Plan management
    Route::resource('plans', AdminPlanController::class)->except(['show']);
    Route::post('plans/{plan}/toggle-active', [AdminPlanController::class, 'toggleActive'])->name('plans.toggle-active');

    // Invoice management
    Route::resource('invoices', AdminInvoiceController::class);

    // Gateway management
    Route::get('gateways', [AdminGatewayController::class, 'index'])->name('gateways.index');
    Route::get('gateways/{gateway}', [AdminGatewayController::class, 'show'])->name('gateways.show');
    Route::post('gateways/{gateway}/restart', [AdminGatewayController::class, 'restart'])->name('gateways.restart');
    Route::delete('gateways/{gateway}', [AdminGatewayController::class, 'destroy'])->name('gateways.destroy');

    // System configuration management
    Route::get('configs', [AdminSystemConfigController::class, 'index'])->name('configs.index');
    Route::get('configs/{system_config}/edit', [AdminSystemConfigController::class, 'edit'])->name('configs.edit');
    Route::put('configs/{system_config}', [AdminSystemConfigController::class, 'update'])->name('configs.update');

    // Alert management
    Route::get('alerts', [AdminAlertController::class, 'index'])->name('alerts.index');
    Route::get('alerts/{alert}', [AdminAlertController::class, 'show'])->name('alerts.show');
    Route::post('alerts/{alert}/resolve', [AdminAlertController::class, 'resolve'])->name('alerts.resolve');
    Route::delete('alerts/{alert}', [AdminAlertController::class, 'destroy'])->name('alerts.destroy');

    // System log management
    Route::get('logs', [AdminSystemLogController::class, 'index'])->name('logs.index');
    Route::get('logs/export', [AdminSystemLogController::class, 'export'])->name('logs.export');
    Route::get('logs/{systemLog}', [AdminSystemLogController::class, 'show'])->name('logs.show');
});

// Webhook routes (no authentication required - signature validation in controller)
Route::post('/webhook/baileys', [WebhookController::class, 'baileys'])->name('webhook.baileys');
Route::post('/webhook/waha', [WebhookController::class, 'waha'])->name('webhook.waha');

require __DIR__.'/auth.php';
