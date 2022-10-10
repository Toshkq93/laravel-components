<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Services\ResourceService;

final class MakeResourceCommand extends Command
{
    protected $hidden = true;
    protected $description = 'Create Resource';
    protected $signature = 'create:resource
                            {name}';

    public function __construct(
        private ResourceService $service,
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->service->setArgument($this->getNameInput());

        $this->service->create();

        return self::SUCCESS;
    }

    private function getNameInput(): string
    {
        return trim($this->argument('name'));
    }
}
