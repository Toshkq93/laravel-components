<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Services\Service;

final class MakeServiceCommand extends Command
{
    protected $hidden = true;
    protected $description = 'Create Service';
    protected $signature = 'make:service
                            {name}
                            {?--dto}
                            {?--repository}
                            {--choice}';

    public function __construct(
        private Service $service
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->service->setArgument($this->getNameInput());
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
