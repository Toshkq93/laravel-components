<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Illuminate\Foundation\Application;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;

class RepositoryService
{
    /** @var array|string[] */
    private array $methods = [
        'index',
        'create',
        'update',
        'show',
        'delete'
    ];

    /** @var string */
    private string $argument;

    /** @var Application */
    private Application $laravel;

    /** @var array|null */
    private null|array $option;

    /** @var string */
    private string $classInput;

    /** @var string */
    private string $classOutput;

    /** @var string */
    private string $namespaceInput;

    /** @var string */
    private string $namespaceOutput;

    /** @var string */
    private string $name;

    /** @var string */
    private string $namespace;

    /**
     * @param string $argument
     */
    public function setArgument(string $argument): void
    {
        $this->argument = $argument;
    }

    /**
     * @param Application $laravel
     */
    public function setLaravel(Application $laravel): void
    {
        $this->laravel = $laravel;
    }

    /**
     * @param array|null $option
     */
    public function setOption(?array $option): void
    {
        $this->option = $option;
    }

    /**
     * @return void
     */
    public function createInterface(): void
    {
        $fileInterface = File::exists(config('component.paths.rootPaths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . "i{$this->className()}Repository.php");

        if (!$fileInterface or $this->option['choice']) {
            File::makeDirectory(
                config('component.paths.rootPaths.repository') . $this->getFolderPath(), 0777,
                true,
                true
            );

            $this->generatePHPCodeByInterface();
        }
    }

    /**
     * @return void
     */
    public function createRepository(): void
    {
        File::makeDirectory(
            config('component.paths.repository') . $this->getFolderPath(), 0777,
            true,
            true
        );

        $this->name = Str::ucfirst($this->className()) . 'Repository';
        $this->namespace = config('component.namespaces.repository') . $this->getFolderPath();
        $model = $this->laravel->make('App\\Models' . $this->argument);

        $classModels = new ReflectionClass(get_class($model));

        $this->generatePHPCodeByFile($classModels);
    }

    /**
     * @param ReflectionClass $model
     * @return void
     */
    private function generatePHPCodeByFile(ReflectionClass $model): void
    {
        $file = new PhpFile();
        $namespaceInterface = $this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface();

        $namespace = $file
            ->addNamespace($this->namespace)
            ->addUse($namespaceInterface)
            ->addUse($model->getName());

        $class = $namespace
            ->addClass($this->name)
            ->addImplement($namespaceInterface);

        foreach ($this->methods as $method) {
            $methodFile = $class
                ->addMethod($method)
                ->setPublic();

            if ($this->option['dto']) {
                $this->getDataByDTO($method);

                $namespace
                    ->addUse($this->namespaceOutput)
                    ->addUse($this->namespaceInput)
                    ->addUse(Collection::class);

                $methodFile
                    ->addComment('@inheritdoc')
                    ->addParameter('filter')
                    ->setType($this->namespaceInput);

                $this->addBody($method, $methodFile);

                $this->addReturnByFile($method, $methodFile, $namespace);
            }
        }

        if ($this->option['dto']) {
            $this->addMethodToDTO($class, $model);
            $this->addMethodToDTOCollection($class);
        }

        $file = File::put(
            config('component.paths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->name . '.php',
            $file
        );

        if ($file){
            $this->createBind();
        }
    }

    /**
     * @return void
     */
    private function createBind(): void
    {
        $provider = file(app_path('Providers\\AppServiceProvider.php'));

        $interface = DIRECTORY_SEPARATOR . $this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface();
        $class = DIRECTORY_SEPARATOR . $this->namespace . DIRECTORY_SEPARATOR . $this->name;

        $lineBind = "\t\t" . '$this->app->bind(' . $interface . '::class, ' . $class . '::class);' . PHP_EOL;

        $searchMethod = 'public function boot()';

        foreach ($provider as $key => $line) {
            if (Str::contains($line, $searchMethod)) {
                array_splice($provider, $key + 2, 0, $lineBind);
            }
        }

        File::put(app_path('Providers\\AppServiceProvider.php'), $provider);
    }

    /**
     * @param ClassType $class
     * @param ReflectionClass $model
     * @return void
     */
    private function addMethodToDTO(ClassType $class, ReflectionClass $model): void
    {
        $method = $class
            ->addMethod('toDTO')
            ->setPrivate();

        $method
            ->addComment('@param ' . $this->className() . ' $' . Str::lcfirst($this->className()));
        $method
            ->addComment('@return ' . $this->className() . 'Output');

        $method
            ->addParameter(Str::lcfirst($this->className()))
            ->setType($model->getName());

        $method
            ->setReturnType(config('component.namespaces.output') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->className() . 'Output');

        $method
            ->setBody('return new ' . $this->className() . 'Output($' . Str::lcfirst($this->className()) . '->toArray());');
    }

    /**
     * @param ClassType $class
     * @return void
     */
    private function addMethodToDTOCollection(ClassType $class): void
    {
        $methodCollection = $class
            ->addMethod('toDTOCollection')
            ->setPrivate();

        $methodCollection
            ->addComment('@param Collection' . ' $' . Str::lcfirst($this->className() . 's'));
        $methodCollection
            ->addComment('@return ' . $this->className() . 'OutputCollection');

        $methodCollection
            ->addParameter(Str::lcfirst($this->className()) . 's')
            ->setType(Collection::class);

        $methodCollection
            ->setReturnType(config('component.namespaces.output') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->className() . 'OutputCollection');

        $methodCollection
            ->addBody('$list = [];')
            ->addBody('')
            ->addBody('foreach ($' . Str::lcfirst($this->className()) . 's as $' . Str::lcfirst($this->className()) . ') {')
            ->addBody('$list[] = $this->toDTO($' . Str::lcfirst($this->className()) . ');')
            ->addBody('}')
            ->addBody('')
            ->addBody('return new ?(
            items: $list
            );', [new Literal($this->className() . 'OutputCollection')]);
    }

    /**
     * @param string $method
     * @param Method $methodFile
     * @return void
     */
    private function addBody(string $method, Method $methodFile): void
    {
        switch ($method) {
            case 'index':
                $methodFile
                    ->addBody('return $this->toDTOCollection(' . $this->className() . '::get());');
                break;
            case 'create':
                $methodFile
                    ->addBody('$' . Str::lcfirst($this->className()) . ' = ' . $this->className() . '::create((new $filter())->toArray());')
                    ->addBody('')
                    ->addBody('return $this->toDTO($' . Str::lcfirst($this->className()) . ');');
                break;
            case 'show':
                $methodFile
                    ->addBody('return $this->toDTO(' . $this->className() . '::findOrFail($filter->getId()));');
                break;
            case 'update':
                $methodFile
                    ->addBody('$' . Str::lcfirst($this->className()) . ' = ' . $this->className() . '::findOrFail($filter->getId());')
                    ->addBody('')
                    ->addBody('return $' . Str::lcfirst($this->className()) . '->update($filter->toArray());');
                break;
            case 'delete':
                $methodFile
                    ->addBody('$' . Str::lcfirst($this->className()) . ' = ' . $this->className() . '::findOrFail($filter->getId());')
                    ->addBody('')
                    ->addBody('return $' . Str::lcfirst($this->className()) . '->delete();');
                break;
        }
    }

    /**
     * @return void
     */
    private function generatePHPCodeByInterface(): void
    {
        $interface = new PhpFile();
        $namespace = $interface
            ->addNamespace($this->getNamespaceInterface());

        $interfaceFile = $namespace
            ->addInterface($this->getNameInterface());

        foreach ($this->methods as $method) {
            $methodInterface = $interfaceFile
                ->addMethod($method)
                ->setPublic();

            if ($this->option['dto']) {

                $this->getDataByDTO($method);

                $namespace
                    ->addUse($this->namespaceOutput)
                    ->addUse($this->namespaceInput);

                $methodInterface
                    ->addParameter('filter')
                    ->setType($this->namespaceInput);

                $methodInterface
                    ->addComment('@param ' . $this->classInput . ' $filter');

                $this->addReturnByInterface($method, $methodInterface, $namespace);
            }
        }

        File::put(
            config('component.paths.rootPaths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameInterface() . '.php',
            $interface
        );
    }

    /**
     * @param string $method
     * @param Method $methodInterface
     * @param PhpNamespace $namespace
     * @return void
     */
    private function addReturnByInterface(string $method, Method $methodInterface, PhpNamespace $namespace): void
    {
        switch ($method) {
            case 'index':
                $methodInterface
                    ->addComment("@return {$this->classOutput}Collection")
                    ->setReturnType($this->namespaceOutput . 'Collection');
                $namespace->addUse($this->namespaceOutput . 'Collection');
                break;
            case 'create':
            case 'show':
                $methodInterface
                    ->addComment("@return {$this->classOutput}")
                    ->setReturnType($this->namespaceOutput);
                break;
            case 'update':
            case 'delete':
                $methodInterface
                    ->addComment("@return bool")
                    ->setReturnType('bool');
                break;
        }
    }

    /**
     * @param string $method
     * @param Method $methodInterface
     * @param PhpNamespace $namespace
     * @return void
     */
    private function addReturnByFile(string $method, Method $methodInterface, PhpNamespace $namespace): void
    {
        switch ($method) {
            case 'index':
                $methodInterface
                    ->setReturnType($this->namespaceOutput . 'Collection');
                $namespace->addUse($this->namespaceOutput . 'Collection');
                break;
            case 'create':
            case 'show':
                $methodInterface
                    ->setReturnType($this->namespaceOutput);
                break;
            case 'update':
            case 'delete':
                $methodInterface
                    ->setReturnType('bool');
                break;
        }
    }

    /**
     * @param $method
     * @return void
     */
    private function getDataByDTO($method): void
    {
        $this->classInput = Str::ucfirst($method) . $this->className() . 'Input';
        $this->namespaceInput = config('component.namespaces.input') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->classInput;
        switch ($method) {
            case 'index':
                $this->classOutput = $this->className() . 'Output';
                $this->namespaceOutput = config('component.namespaces.output') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->classOutput;
                break;
            case 'create':
            case 'show':
                $this->classOutput = $this->className() . 'Output';
                $this->namespaceOutput = config('component.namespaces.output') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->classOutput;
                break;
        }
    }

    /**
     * @return string
     */
    private function getFolderPath(): string
    {
        return Str::beforeLast($this->argument, '\\');
    }

    /**
     * @return string
     */
    private function getNamespaceInterface(): string
    {
        return config('component.namespaces.contracts.repository') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    private function getNameInterface(): string
    {
        return 'i' . Str::ucfirst($this->className()) . 'Repository';
    }

    /**
     * @return string
     */
    private function className(): string
    {
        return class_basename($this->argument);
    }
}
