<?php

namespace Toshkq93\Components\Console\Commands;

use App\Exceptions\Entities\EntityNotFoundException;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Toshkq93\Components\Services\Service;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service
                            {name}
                            {?--dto}
                            {?--repository}';

    /** @var bool */
    protected $hidden = true;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Service';

    public function __construct(
        private Service $service
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
        $this->service->setLaravel($this->laravel);
        $this->service->setOption($this->options());

        if (!File::exists(config('path.rootPaths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . "i{$this->className()}Service.php")) {
            $this->service->createInterface();
        }

        $this->service->createService();


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
