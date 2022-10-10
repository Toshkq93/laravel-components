<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Services\ControllerService;

final class MakeControllerCommand extends Command
{
    protected $hidden = true;
    protected $description = 'Create Controller';
    protected $signature = 'create:controller {name}
                            {?--service}
                            {?--resource}
                            {?--request}
                            {?--dto}
                            {?--primary}';

    public function __construct(
        private ControllerService $service
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->service->setArgument($this->getNameInput());
        $this->service->setOption($this->options());
        $this->service->setPrimaryKey($this->option('primary'));

        $this->service->create();

        return self::SUCCESS;
    }

    private function getNameInput(): string
    {
        return trim($this->argument('name'));
    }
}
