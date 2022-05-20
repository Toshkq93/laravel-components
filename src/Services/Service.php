<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Illuminate\Foundation\Application;
use Nette\PhpGenerator\PhpNamespace;
use Toshkq93\Components\Enums\MethodsByClassEnum;

class Service
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
        if ($this->option['choice'] or !File::exists(config('component.paths.rootPaths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . "i{$this->className()}Service.php")) {
            File::makeDirectory(
                config('component.paths.rootPaths.service') . $this->getFolderPath(), 0777,
                true,
                true
            );

            $this->generatePHPCodeByInterface();
        }
    }

    /**
     * @return void
     */
    public function createService(): void
    {
        File::makeDirectory(
            config('component.paths.service') . $this->getFolderPath(), 0777,
            true,
            true
        );

        $this->name = Str::ucfirst($this->className()) . 'Service';
        $this->namespace = config('component.namespaces.service') . $this->getFolderPath();

        $this->generatePHPCodeByFile();
    }

    /**
     * @return void
     */
    private function generatePHPCodeByFile(): void
    {
        $namespaceInterface = $this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface();

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->namespace)
            ->addUse($namespaceInterface);


        $class = $namespace
            ->addClass($this->name)
            ->addImplement($namespaceInterface);

        if ($this->option['repository']) {
            $namespaceRepositoryClass = config('component.namespaces.contracts.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . 'i' . $this->className() . 'Repository';

            $namespace
                ->addUse($namespaceRepositoryClass);
            $class
                ->addMethod('__construct')
                ->addPromotedParameter('repository')
                ->setPrivate()
                ->setType($namespaceRepositoryClass);
        }

        foreach ($this->methods as $method) {
            $methodFile = $class
                ->addMethod($method)
                ->setPublic();

            if ($this->option['dto']) {
                $this->getDataByDTOandFilter($method);

                $namespace
                    ->addUse($this->namespaceOutput)
                    ->addUse($this->namespaceInput);

                $methodFile
                    ->addComment('@inheritdoc')
                    ->addParameter('filter')
                    ->setType($this->namespaceInput);

                if ($this->option['repository']) {
                    $methodFile
                        ->addBody('return $this->repository->' . $method . '($filter);');
                }

                $this->addReturnByFile($method, $methodFile, $namespace);
            }
        }

        $file = File::put(
            config('component.paths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->name . '.php',
            $file
        );

        if ($file) {
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
                array_splice($provider, $key + 4, 0, $lineBind);
            }
        }

        File::put(app_path('Providers\\AppServiceProvider.php'), $provider);
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

                $this->getDataByDTOandFilter($method);

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
            config('component.paths.rootPaths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameInterface() . '.php',
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
            case MethodsByClassEnum::INDEX:
                $methodInterface
                    ->addComment("@return {$this->classOutput}Collection")
                    ->setReturnType($this->namespaceOutput . 'Collection');
                $namespace->addUse($this->namespaceOutput . 'Collection');
                break;
            case MethodsByClassEnum::CREATE:
            case MethodsByClassEnum::SHOW:
                $methodInterface
                    ->addComment("@return {$this->classOutput}")
                    ->setReturnType($this->namespaceOutput);
                break;
            case MethodsByClassEnum::UPDATE:
            case MethodsByClassEnum::DELETE:
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
            case MethodsByClassEnum::INDEX:
                $methodInterface
                    ->setReturnType($this->namespaceOutput . 'Collection');
                $namespace->addUse($this->namespaceOutput . 'Collection');
                break;
            case MethodsByClassEnum::CREATE:
            case MethodsByClassEnum::SHOW:
                $methodInterface
                    ->setReturnType($this->namespaceOutput);
                break;
            case MethodsByClassEnum::UPDATE:
            case MethodsByClassEnum::DELETE:
                $methodInterface
                    ->setReturnType('bool');
                break;
        }
    }

    /**
     * @param $method
     * @return void
     */
    private function getDataByDTOandFilter($method): void
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
        return config('component.namespaces.contracts.service') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    private function getNameInterface(): string
    {
        return 'i' . Str::ucfirst($this->className()) . 'Service';
    }

    /**
     * @return string
     */
    private function className(): string
    {
        return class_basename($this->argument);
    }
}
