<?php

namespace Rabsana\Exchanger\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Rabsana\Exchanger\Contracts\Interfaces\Exchanger;
use Rabsana\Exchanger\Exceptions\ExchangerClassIsNotFoundException;

class ExchangerServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->registerPublishes();
        $this->registerResources();
    }

    public function register()
    {
        $this->registerSingletons()
            ->registerBinds()
            ->loadCommands();
    }

    protected function loadCommands()
    {
        // $this->commands([
        //     ExchangerCommand::class
        // ]);
    }

    protected function registerPublishes(): ExchangerServiceProvider
    {
        $this->publishConfigs()
            ->publishMigrations()
            ->publishAssets()
            ->publishLangs()
            ->publishViews()
            ->publishAll();

        return $this;
    }

    protected function publishConfigs(): ExchangerServiceProvider
    {
        $this->publishes([
            __DIR__ . "/../../config/rabsana-exchanger.php" => config_path('rabsana-exchanger.php')
        ], 'rabsana-exchanger-config');

        return $this;
    }

    protected function publishMigrations(): ExchangerServiceProvider
    {
        // $this->publishes([
        //     __DIR__ . "/../../database/migrations/2021_06_25_130755_create_exchanger_table.php"                            => database_path('migrations/2021_06_25_130755_create_exchanger_table.php'),
        // ], 'rabsana-exchanger-migrations');

        return $this;
    }

    protected function publishAssets(): ExchangerServiceProvider
    {
        // $this->publishes([
        //     __DIR__ . "/../../assets/" => public_path('vendor/rabsana/exchanger')
        // ], 'rabsana-exchanger-assets');

        return $this;
    }

    protected function publishLangs(): ExchangerServiceProvider
    {
        // $this->publishes([
        //     __DIR__ . "/../../resources/lang" => resource_path("lang/exchanger")
        // ], 'rabsana-exchanger-langs');

        return $this;
    }

    protected function publishViews(): ExchangerServiceProvider
    {
        // $this->publishes([
        //     __DIR__ . "/../../resources/views" => resource_path("views/vendor/exchanger")
        // ], 'rabsana-exchanger-views');

        return $this;
    }

    protected function publishAll(): ExchangerServiceProvider
    {
        $this->publishes(self::$publishes[ExchangerServiceProvider::class], 'rabsana-exchanger-publish-all');

        return $this;
    }

    protected function registerResources(): ExchangerServiceProvider
    {
        $this->registerMigrations()
            ->registerTranslations()
            ->registerViews()
            ->registerApiRoutes()
            ->registerAdminApiRoutes();


        return $this;
    }

    protected function registerSingletons(): ExchangerServiceProvider
    {
        $this->app->singleton(Exchanger::class, function ($app) {
            $className = "Rabsana\\Exchanger\\Exchangers\\" . Str::ucfirst(Str::camel(Config::get("rabsana-exchanger.exchanger", 'binance')));

            if (!class_exists($className)) {
                throw new ExchangerClassIsNotFoundException("The exchanger class: \"{$className}\" not found. please check your exchanger config");
            }

            return new $className();
        });

        return $this;
    }

    protected function registerBinds(): ExchangerServiceProvider
    {
        // $this->app->bind(Exchanger::class, function ($app) {
        //     return new ExchangerClass();
        // });

        return $this;
    }

    protected function registerMigrations(): ExchangerServiceProvider
    {
        $this->loadMigrationsFrom(__DIR__ . "/../../database/migrations");
        return $this;
    }

    protected function registerTranslations(): ExchangerServiceProvider
    {
        $this->loadTranslationsFrom(__DIR__ . "/../../resources/lang", 'exchanger');
        return $this;
    }

    protected function registerViews(): ExchangerServiceProvider
    {
        $this->loadViewsFrom(__DIR__ . "/../../resources/views", 'exchanger');
        return $this;
    }

    protected function registerApiRoutes(): ExchangerServiceProvider
    {
        Route::group($this->apiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . "/../../routes/api.php");
        });
        return $this;
    }

    protected function registerAdminApiRoutes(): ExchangerServiceProvider
    {
        Route::group($this->adminApiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . "/../../routes/admin-api.php");
        });
        return $this;
    }

    protected function apiRouteConfiguration(): array
    {
        return [
            'domain'        => config('rabsana-exchanger.domain', null),
            'namespace'     => NULL,
            'prefix'        => config('rabsana-exchanger.path', 'rabsana-exchanger'),
            'as'            => 'rabsana-exchanger.',
            'middleware'    => config('rabsana-exchanger.apiMiddlewares.group', 'api'),
        ];
    }

    protected function adminApiRouteConfiguration(): array
    {
        return [
            'domain'        => config('rabsana-exchanger.domain', null),
            'namespace'     => NULL,
            'prefix'        => config('rabsana-exchanger.path', 'rabsana-exchanger'),
            'as'            => 'rabsana-exchanger.',
            'middleware'    =>  config('rabsana-exchanger.adminApiMiddlewares.group', 'web'),
        ];
    }
}
