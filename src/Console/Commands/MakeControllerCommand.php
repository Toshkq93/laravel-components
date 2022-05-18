<?php

namespace Toshkq93\Components\Console\Commands;

use File;
use Illuminate\Console\Command;
use Toshkq93\Components\Services\ControllerService;

class MakeControllerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:controller {name}
                            {?--service}
                            {?--resource}
                            {?--request}
                            {?--dto}';

    /** @var bool  */
    protected $hidden = true;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Controller';

    public function __construct(
        private ControllerService $service
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
        $baseController = config('path.paths.controller') . 'BaseController.php';

        $this->service->setArgument($this->getNameInput());
        $this->service->setOptions($this->options());

        if (!File::exists($baseController)){
            $this->service->createBaseController();
        }

        $this->service->create();

        return 0;
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
