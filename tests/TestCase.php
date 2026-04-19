<?php

declare(strict_types=1);

namespace Devrkb21\Bkash\Tests;

use Devrkb21\Bkash\BkashServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BkashServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', ['driver' => 'array']);

        $app['config']->set('bkash', [
            'environment' => 'sandbox',
            'base_url' => 'https://tokenized.sandbox.bka.sh/v2/tokenized-checkout',
            'app_key' => 'app_key',
            'app_secret' => 'app_secret',
            'username' => 'username',
            'password' => 'password',
            'timeout' => 30,
            'cache' => true,
            'enable_routes' => false,
            'cache_keys' => [
                'token' => 'bkash.test.token',
            ],
        ]);
    }

    public function artisan($command, $parameters = [])
    {
        return parent::artisan($command, $parameters);
    }

    public function be(Authenticatable $user, $driver = null)
    {
        return parent::be($user, $driver);
    }

    public function call($method, $uri, $parameters = [], $files = [], $server = [], $content = null, $changeHistory = true)
    {
        return parent::call($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public function seed($class = 'DatabaseSeeder')
    {
        return parent::seed($class);
    }
}
