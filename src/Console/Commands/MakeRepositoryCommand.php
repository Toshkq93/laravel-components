<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Services\RepositoryService;

final class MakeRepositoryCommand extends Command
{
    protected $hidden = true;
    protected $description = 'Create repository';
    protected $signature = 'make:repository
                            {name}
                            {?--dto}
                            {--choice}';

    public function __construct(
        private RepositoryService $service
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->service->setArgument($this->getNameInput());
        $this->service->setLaravel($this->laravel);
        $this->service->setOption($this->options());

        $this->service->createBase();

        $this->service->create();

        return self::SUCCESS;
    }

    private function getNameInput(): string
    {
        return trim($this->argument('name'));
    }
}
