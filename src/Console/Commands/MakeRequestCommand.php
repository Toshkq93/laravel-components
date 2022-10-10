<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Services\RequestService;

final class MakeRequestCommand extends Command
{
    protected $hidden = true;
    protected $description = 'Create Request';
    protected $signature = 'create:request
                            {name}
                            {--properties}';

    public function __construct(
        private RequestService $service
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->service->setArgument($this->getNameInput());
        $this->service->setProperties($this->option('properties'));

        $this->service->create();

        return self::SUCCESS;
    }

    private function getNameInput(): string
    {
        return trim($this->argument('name'));
    }
}
