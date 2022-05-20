<?php

namespace Toshkq93\Components\Console\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Toshkq93\Components\Enums\DTONameEnum;
use Toshkq93\Components\Services\DTOService;

class MakeDTOCommand extends Command
{
    /** @var string */
    protected $signature = 'make:dto
                            {name}
                            {--properties=}';

    /** @var string */
    protected $description = 'Create DTO';

    /** @var bool */
    protected $hidden = true;

    public function __construct(
        private DTOService $service
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->service->setFolder(Str::contains($this->getNameInput(), 'Output') ? DTONameEnum::OUTPUT : DTONameEnum::INPUT);
        $this->service->setProperties($this->option('properties'));
        $this->service->setArgument($this->getNameInput());

        $this->service->createBaseDTO();

        $this->service->create();

        return self::SUCCESS;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    private function getNameInput(): string
    {
        return trim($this->argument('name'));
    }
}
