<?php

declare(strict_types=1);

namespace Devrkb21\Bkash;

use Devrkb21\Bkash\Contracts\BkashClientContract;
use Devrkb21\Bkash\Contracts\AgreementServiceContract;
use Devrkb21\Bkash\Contracts\AuthServiceContract;
use Devrkb21\Bkash\Contracts\PaymentServiceContract;
use Devrkb21\Bkash\Contracts\RefundServiceContract;
use Devrkb21\Bkash\Contracts\WebhookServiceContract;
use Devrkb21\Bkash\Http\BkashClient;
use Devrkb21\Bkash\Services\AgreementService;
use Devrkb21\Bkash\Services\AuthService;
use Devrkb21\Bkash\Services\PaymentService;
use Devrkb21\Bkash\Services\RefundService;
use Devrkb21\Bkash\Services\WebhookService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class BkashServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bkash.php', 'bkash');

        $this->app->singleton(BkashClientContract::class, function ($app): BkashClientContract {
            return new BkashClient(
                $app->make(HttpFactory::class),
                (array) $app['config']->get('bkash', [])
            );
        });

        $this->app->singleton(AuthServiceContract::class, function ($app): AuthService {
            return new AuthService(
                $app->make(BkashClientContract::class),
                $app->make(CacheRepository::class),
                (array) $app['config']->get('bkash', [])
            );
        });

        $this->app->singleton(PaymentServiceContract::class, function ($app): PaymentService {
            return new PaymentService(
                $app->make(BkashClientContract::class),
                $app->make(AuthServiceContract::class)
            );
        });

        $this->app->singleton(AgreementServiceContract::class, function ($app): AgreementService {
            return new AgreementService(
                $app->make(BkashClientContract::class),
                $app->make(AuthServiceContract::class)
            );
        });

        $this->app->singleton(RefundServiceContract::class, function ($app): RefundService {
            return new RefundService(
                $app->make(BkashClientContract::class),
                $app->make(AuthServiceContract::class)
            );
        });

        $this->app->singleton(WebhookServiceContract::class, function (): WebhookService {
            return new WebhookService();
        });

        $this->app->singleton(BkashManager::class, function ($app): BkashManager {
            return new BkashManager(
                $app->make(PaymentServiceContract::class),
                $app->make(AuthServiceContract::class),
                $app->make(AgreementServiceContract::class),
                $app->make(RefundServiceContract::class)
            );
        });

        $this->app->singleton('bkash', function ($app): BkashManager {
            return $app->make(BkashManager::class);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/bkash.php' => $this->app->basePath('config/bkash.php'),
        ], 'bkash-config');

        if ($this->app['config']->get('bkash.enable_routes') === true) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}
