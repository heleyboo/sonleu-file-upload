<?php

namespace SonLeu\File\App\Providers;

use Illuminate\Support\ServiceProvider;

class FileServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../Database/Migrations' => database_path('migrations'),
            __DIR__ . '/../../Config/file.php' => config_path('file.php'),
        ]);
    }
}
