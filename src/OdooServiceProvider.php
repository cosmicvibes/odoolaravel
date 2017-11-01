<?php

namespace Cosmicvibes\Odoolaravel;

use Illuminate\Support\ServiceProvider;

class OdooServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/laradoo.php' => config_path('laradoo.php'),
        ]);

        $this->publishes([
            __DIR__.'/../config/odoolaravel.php' => config_path('odoolaravel.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
