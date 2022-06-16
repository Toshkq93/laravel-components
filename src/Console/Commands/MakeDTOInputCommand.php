<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Enums\DTONameEnum;
use Toshkq93\Components\Services\InputDTOService;

class MakeDTOInputCommand extends Command
{
    /** @var string */
    protected $signature = 'make:input-dto
                            {name}
                            {--properties=}';

    /** @var string */
    protected $description = 'Create input DTO';

    /** @var bool */
    protected $hidden = true;

    public function __construct(
        private InputDTOService $service
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
        $this->service->setFolder(DTONameEnum::INPUT);
        $this->service->setProperties($this->option('properties'));
        $this->service->setArgument($this->getNameInput());

        $this->service->createInterfaces();

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
