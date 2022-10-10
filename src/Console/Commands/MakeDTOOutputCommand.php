<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Enums\DTONameEnum;
use Toshkq93\Components\Services\DTOService;

final class MakeDTOOutputCommand extends Command
{
    protected $hidden = true;
    protected $description = 'Create DTO';
    protected $signature = 'make:output-dto
                            {name}
                            {--properties=}';

    public function __construct(
        private DTOService $service
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->service->setFolder(DTONameEnum::OUTPUT);
        $this->service->setProperties($this->option('properties'));
        $this->service->setArgument($this->getNameInput());

        $this->service->createBaseDTO();

        $this->service->create();

        return self::SUCCESS;
    }

    private function getNameInput(): string
    {
        return trim($this->argument('name'));
    }
}
