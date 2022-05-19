<?php

namespace Toshkq93\Components\Console\Commands;

use Exception;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Toshkq93\Components\Services\RepositoryService;

class MakeRepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository
                            {name}
                            {?--dto}
                            {--choice}';

    /** @var bool  */
    protected $hidden = true;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create repository';

    public function __construct(
        private RepositoryService $service
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
        $fileInterface = File::exists(config('component.paths.rootPaths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . "i{$this->className()}Repository.php");

        $this->service->setArgument($this->getNameInput());
        $this->service->setLaravel($this->laravel);
        $this->service->setOption($this->options());

        if (!$fileInterface or $this->option('choice')) {
            $this->service->createInterface();
        }

        $this->service->createRepository();


        return self::SUCCESS;
    }

    /**
     * @return string
     */
    private function className(): string
    {
        return class_basename($this->getNameInput());
    }

    /**
     * @return string
     */
    private function getFolderPath(): string
    {
        return Str::beforeLast($this->getNameInput(), '\\');
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
