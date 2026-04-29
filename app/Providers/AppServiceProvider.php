<?php

namespace App\Providers;

use App\Services\AlertService;
use App\Services\ApiTokenService;
use App\Services\AutoReplyService;
use App\Services\BaileysGatewayClient;
use App\Services\BillingService;
use App\Services\BroadcastService;
use App\Services\Contracts\AlertServiceInterface;
use App\Services\Contracts\ApiTokenServiceInterface;
use App\Services\Contracts\AutoReplyServiceInterface;
use App\Services\Contracts\BaileysGatewayClientInterface;
use App\Services\Contracts\BillingServiceInterface;
use App\Services\Contracts\BroadcastServiceInterface;
use App\Services\Contracts\DeviceServiceInterface;
use App\Services\Contracts\MessageServiceInterface;
use App\Services\Contracts\QuotaServiceInterface;
use App\Services\Contracts\ReminderServiceInterface;
use App\Services\Contracts\SubscriptionServiceInterface;
use App\Services\Contracts\TemplateServiceInterface;
use App\Services\DeviceService;
use App\Services\MessageService;
use App\Services\QuotaService;
use App\Services\ReminderService;
use App\Services\SubscriptionService;
use App\Services\TemplateService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DeviceServiceInterface::class, DeviceService::class);
        $this->app->bind(QuotaServiceInterface::class, QuotaService::class);
        $this->app->bind(MessageServiceInterface::class, MessageService::class);
        $this->app->bind(TemplateServiceInterface::class, TemplateService::class);
        $this->app->bind(BroadcastServiceInterface::class, BroadcastService::class);
        $this->app->bind(AutoReplyServiceInterface::class, AutoReplyService::class);
        $this->app->bind(BaileysGatewayClientInterface::class, BaileysGatewayClient::class);
        $this->app->bind(ApiTokenServiceInterface::class, ApiTokenService::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(BillingServiceInterface::class, BillingService::class);
        $this->app->bind(AlertServiceInterface::class, AlertService::class);
        $this->app->bind(ReminderServiceInterface::class, ReminderService::class);

        // Register TemplateService with logger dependency
        $this->app->bind(TemplateService::class, function ($app) {
            return new TemplateService($app->make('log'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
