<?php

namespace Toshkq93\Components\Console\Commands;

use Exception;
use File;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use ReflectionClass;
use Str;
use Symfony\Component\Console\Input\InputOption;
use Toshkq93\Components\Services\ModelService;

class ComponentsCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:components';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'make:components';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create DTOs, service, repository, controller, resources, requests';
    /** @var string */
    private string $path;

    public function __construct(
        Filesystem           $files,
        private ModelService $service
    )
    {
        parent::__construct($files);
    }

    /**
     * @return false|void
     */
    public function handle(): bool
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "' . $this->getNameInput() . '" is reserved by PHP.');
            return self::FAILURE;
        }

        try {
            $model = $this->laravel->make($this->getNameInput());
        } catch (Exception $exception) {
            $this->error("Model {$this->className()} not found!");
            return self::FAILURE;
        }

        if ($this->option('all')) {
            $this->input->setOption('dto', true);
            $this->input->setOption('repository', true);
            $this->input->setOption('service', true);
            $this->input->setOption('resource', true);
            $this->input->setOption('request', true);
            $this->input->setOption('controller', true);
        }
        $class = new ReflectionClass(get_class($model));

        $this->path = Str::studly(Str::after(dirname($class->getFileName()), 'Models'));

        if ($this->option('dto')) {
            $properties = $this->service->getProperties($model);

            if (!$properties) {
                $table = ($model)->getTable();
                $this->error("Table {$table} not found. Please create migration and use php artisan migrate");
                return self::FAILURE;
            }

            $this->createDTO($properties);
        }

        if ($this->option('repository')) {
            $this->createRepository();
        }

        if ($this->option('service')) {
            $this->createService();
        }

        if ($this->option('resource')) {
            $properties = $properties ?? [];
            $this->createResource($properties);
        }

        if ($this->option('request')) {
            $properties = $properties ?? [];
            $this->createRequest($properties);
        }

        if ($this->option('controller')) {
            $this->createController();
        }

        return self::SUCCESS;
    }

    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }

    /**
     * @param $properties
     * @return void
     */
    private function createDTO($properties): void
    {
        Artisan::call('make:dto', [
            'name' => "{$this->classPath()}Output",
            '--properties' => $properties
        ]);
        $pathToDTO = 'app\\DTO\\Output' . $this->classPath();

        $this->info("<fg=white;bg=green>Output DTO by the way {$pathToDTO} create success!</>");

        Artisan::call('make:dto', [
            'name' => "{$this->classPath()}Input",
            '--properties' => $properties
        ]);
        $pathToFilter = 'app\\DTO\\Input' . $this->classPath();

        $this->info("<fg=white;bg=green>Input DTO by the way {$pathToFilter} create success!</>");
    }

    /**
     * @return void
     */
    private function createRepository(): void
    {
        $result = false;

        if (File::exists(config('component.paths.rootPaths.repository') . $this->path . DIRECTORY_SEPARATOR . "i{$this->className()}Repository.php")){
            $result = $this->confirm('File i' . $this->className() . 'Repository exist, do you want rewrite this file?', true);
        }

        Artisan::call('make:repository', [
            'name' => "{$this->classPath()}",
            '--dto' => $this->option('dto') ?? false,
            '--choice' => $result
        ]);

        $this->info("<fg=white;bg=green>Repository {$this->className()}Repository create success!</>");
    }

    /**
     * @return void
     */
    private function createService(): void
    {
        $result = false;

        if (File::exists(config('component.paths.rootPaths.service') . $this->path . DIRECTORY_SEPARATOR . "i{$this->className()}Service.php")){
            $result = $this->confirm('File i' . $this->className() . 'Service exist, do you want rewrite this file?', true);
        }


        Artisan::call('make:service', [
            'name' => "{$this->classPath()}",
            '--repository' => $this->option('repository') ?? false,
            '--dto' => $this->option('dto') ?? false,
            '--choice' => $result
        ]);

        $this->info("<fg=white;bg=green>Service {$this->className()}Service create success!</>");
    }

    /**
     * @param $properties
     * @return void
     */
    private function createResource($properties): void
    {
        Artisan::call('create:resource', [
            'name' => "{$this->classPath()}",
            '--properties' => $properties
        ]);
        $path = 'app\\Http\\Resources' . $this->classPath();

        $this->info("<fg=white;bg=green>Resources by the way {$path} create success!</>");
    }

    /**
     * @param $properties
     * @return void
     */
    private function createRequest($properties): void
    {
        Artisan::call('create:request', [
            'name' => "{$this->classPath()}",
            '--properties' => $properties
        ]);
        $path = 'app\\Http\\Requests' . $this->classPath();

        $this->info("<fg=white;bg=green>Requests by the way {$path} create success!</>");
    }

    /**
     * @return void
     */
    private function createController(): void
    {
        Artisan::call("create:controller", [
            'name' => "{$this->classPath()}",
            '--service' => $this->option('service') ?? null,
            '--request' => $this->option('request') ?? null,
            '--resource' => $this->option('resource') ?? null,
            '--dto' => $this->option('dto') ?? null,
        ]);

        $this->info("<fg=white;bg=green>Controller {$this->className()}Controller create success!</>");
    }

    /**
     * @return string
     */
    private function classPath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->className();
    }

    /**
     * @return string
     */
    private function className(): string
    {
        return class_basename($this->argument('name'));
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['dto', 'd', InputOption::VALUE_NONE, 'create DTO classes'],
            ['controller', 'c', InputOption::VALUE_NONE, 'create controller class'],
            ['resource', 'r', InputOption::VALUE_NONE, 'create resource classes'],
            ['service', 's', InputOption::VALUE_NONE, 'create service class'],
            ['request', null, InputOption::VALUE_NONE, 'create request classes'],
            ['repository', 'R', InputOption::VALUE_NONE, 'create repository class'],
            ['all', null, InputOption::VALUE_NONE, 'Generate a dto, controller, resources, requests, service, and repository for the model'],
        ];
    }
}
