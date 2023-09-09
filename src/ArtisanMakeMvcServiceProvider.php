<?php

namespace Meshesha\ArtisanMakeMvc;

use Illuminate\Support\ServiceProvider;

class ArtisanMakeMvcServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */

    public function register()
    {
        $this->commands([
            Commands\MakeMvc::class,
            Commands\UndoMvc::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */

    public function boot()
    {
        $this->publishes([
           __DIR__.'/config/ArtisanMakeMvc.php' =>  config_path('ArtisanMakeMvc.php'),
        ], 'config');

    }
}
