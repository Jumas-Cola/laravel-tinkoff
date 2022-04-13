<?php

namespace Kenvel;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class TinkoffServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/tinkoff.php',
            'comments'
        );

        App::bind('laraveltinkoff', static function () {
            return new LaravelTinkoffClass();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/tinkoff.php' => config_path('tinkoff.php'),
        ], 'config');
    }
}
