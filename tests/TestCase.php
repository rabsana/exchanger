<?php

namespace Rabsana\Exchanger\Tests;

use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Rabsana\Core\Providers\CoreServiceProvider;
use Rabsana\Exchanger\Providers\ExchangerServiceProvider;

class TestCase extends TestbenchTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Config::set(['PACKAGE_ENV' => 'testing']);
    }
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ExchangerServiceProvider::class,
            CoreServiceProvider::class
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'exchangerDB');
        $app['config']->set('database.connections.exchangerDB', [
            'driver'    => 'sqlite',
            'database'  => ':memory:'
        ]);
    }
}
