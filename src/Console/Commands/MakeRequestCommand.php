<?php

namespace Toshkq93\Components\Console\Commands;

use Illuminate\Console\Command;
use Toshkq93\Components\Services\RequestService;

class MakeRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:request
                            {name}
                            {--properties}';

    /** @var bool  */
    protected $hidden = true;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Request';

    public function __construct(
        private RequestService $service
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
        $this->service->setArgument($this->getNameInput());
        $this->service->setProperties($this->option('properties'));

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
