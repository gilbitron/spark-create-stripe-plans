<?php

namespace Gilbitron\Laravel\Spark;

use Illuminate\Support\ServiceProvider;

class CreateStripePlansServiceProvider extends ServiceProvider
{
    protected $commands = [
        \Gilbitron\Laravel\Spark\Console\Commands\CreateStripePlans::class,
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
}