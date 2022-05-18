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

    /** @var Application */
    private Application $laravel;

    /** @var array|null */
    private null|array $option;

    /** @var string */
    private string $classFilter;

    /** @var string */
    private string $classDTO;

    /** @var string */
    private string $namespaceFilter;

    /** @var string */
    private string $namespaceDTO;

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
        $interface = $this->generatePHPCodeByInterface();

        $this->createFile($interface);
    }

    /**
     * @return void
     */
    public function createService(): void
    {
        $this->name = Str::ucfirst($this->className()) . 'Service';
        $this->namespace = config('path.namespaces.service') . $this->getFolderPath();

        $file = $this->generatePHPCodeByFile();

        File::makeDirectory(
            config('path.paths.service') . $this->getFolderPath(), 0777,
            true,
            true
        );

        File::put(
            config('path.paths.service') . DIRECTORY_SEPARATOR . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->name . '.php',
            $file
        );
    }

    /**
     * @return PhpFile
     */
    private function generatePHPCodeByFile(): PhpFile
    {
        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->namespace)
            ->addUse($this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface());


        $class = $namespace
            ->addClass($this->name)
            ->addImplement($this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface());

        if ($this->option['repository']) {
            $namespaceRepositoryClass = config('path.namespaces.contracts.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . 'i' . $this->className() . 'Repository';

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
                    ->addUse($this->namespaceDTO)
                    ->addUse($this->namespaceFilter);

                $methodFile
                    ->addComment('@inheritdoc')
                    ->addParameter('filter')
                    ->setType($this->namespaceFilter);

                $this->addBody($method, $methodFile);

                $this->addReturnByFile($method, $methodFile, $namespace);

            }
        }

        return $file;
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
                    ->addBody('return $this->repository->index($filter);');
                break;
            case 'create':
                $methodFile
                    ->addBody('return $this->repository->create($filter);');
                break;
            case 'show':
                $methodFile
                    ->addBody('return $this->repository->show($filter);');
                break;
            case 'update':
                $methodFile
                    ->addBody('return $this->repository->update($filter);');
                break;
            case 'delete':
                $methodFile
                    ->addBody('return $this->repository->delete($filter);');
                break;
        }
    }

    /**
     * @return PhpFile
     */
    private function generatePHPCodeByInterface(): PhpFile
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
                    ->addUse($this->namespaceDTO)
                    ->addUse($this->namespaceFilter);

                $methodInterface
                    ->addParameter('filter')
                    ->setType($this->namespaceFilter);

                $methodInterface
                    ->addComment('@param ' . $this->classFilter . ' $filter');

                $this->addReturnByInterface($method, $methodInterface, $namespace);
            }
        }

        return $interface;
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
                    ->addComment("@return {$this->classDTO}Collection")
                    ->setReturnType($this->namespaceDTO . 'Collection');
                $namespace->addUse($this->namespaceDTO . 'Collection');
                break;
            case MethodsByClassEnum::CREATE:
            case MethodsByClassEnum::SHOW:
                $methodInterface
                    ->addComment("@return {$this->classDTO}")
                    ->setReturnType($this->namespaceDTO);
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
                    ->setReturnType($this->namespaceDTO . 'Collection');
                $namespace->addUse($this->namespaceDTO . 'Collection');
                break;
            case MethodsByClassEnum::CREATE:
            case MethodsByClassEnum::SHOW:
                $methodInterface
                    ->setReturnType($this->namespaceDTO);
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
        $this->classFilter = Str::ucfirst($method) . $this->className() . 'Filter';
        $this->namespaceFilter = config('path.namespaces.filter') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->classFilter;
        switch ($method) {
            case 'index':
                $this->classDTO = $this->className() . 'DTO';
                $this->namespaceDTO = config('path.namespaces.dto') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->classDTO;
                break;
            case 'create':
            case 'show':
                $this->classDTO = $this->className() . 'DTO';
                $this->namespaceDTO = config('path.namespaces.dto') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->classDTO;
                break;
        }
    }

    /**
     * @param PhpFile $php
     * @return void
     */
    private function createFile(PhpFile $php): void
    {
        File::makeDirectory(
            config('path.rootPaths.service') . $this->getFolderPath(), 0777,
            true,
            true
        );

        File::put(
            config('path.rootPaths.service') . DIRECTORY_SEPARATOR . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameInterface() . '.php',
            $php
        );
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
        return Str::beforeLast($this->laravel->getNamespace() . 'Contracts\\Services' . $this->argument, '\\');
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
