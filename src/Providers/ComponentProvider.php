<?php

namespace Toshkq93\Components\Providers;

use Illuminate\Support\ServiceProvider;
use Toshkq93\Components\Console\Commands\ComponentsCommand;
use Toshkq93\Components\Console\Commands\MakeControllerCommand;
use Toshkq93\Components\Console\Commands\MakeDTOCommand;
use Toshkq93\Components\Console\Commands\MakeRepositoryCommand;
use Toshkq93\Components\Console\Commands\MakeRequestCommand;
use Toshkq93\Components\Console\Commands\MakeResourceCommand;
use Toshkq93\Components\Console\Commands\MakeServiceCommand;

class ComponentProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/component.php', 'component'
        );

        $this->publishes([
            __DIR__ . '/../config/component.php', config_path('component.php')
        ]);

        $this->registerCommand();
    }

    /**
     * @return void
     */
    private function registerCommand(): void
    {
        if ($this->app->runningInConsole()){
            $this->commands([
                ComponentsCommand::class,
                MakeControllerCommand::class,
                MakeServiceCommand::class,
                MakeDTOCommand::class,
                MakeRepositoryCommand::class,
                MakeResourceCommand::class,
                MakeRequestCommand::class,
            ]);
        }
    }
}
