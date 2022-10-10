<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Enums\DTONameEnum;
use Toshkq93\Components\Services\InputDTOService;

final class MakeDTOInputCommand extends Command
{
    protected $hidden = true;
    protected $description = 'Create input DTO';
    protected $signature = 'make:input-dto
                            {name}
                            {--properties=}';

    public function __construct(
        private InputDTOService $service
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->service->setFolder(DTONameEnum::INPUT);
        $this->service->setProperties($this->option('properties'));
        $this->service->setArgument($this->getNameInput());

        $this->service->createInterfaces();

        $this->service->create();

        return self::SUCCESS;
    }

    private function getNameInput(): string
    {
        return trim($this->argument('name'));
    }
}
