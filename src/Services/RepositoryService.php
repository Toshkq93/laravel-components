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
    public function createRepository(): void
    {
        $this->name = Str::ucfirst($this->className()) . 'Repository';
        $this->namespace = config('path.namespaces.repository') . $this->getFolderPath();
        $model = $this->laravel->make('App\\Models' . $this->argument);

        $classModels = new ReflectionClass(get_class($model));

        $file = $this->generatePHPCodeByFile($classModels);

        File::makeDirectory(
            config('path.paths.repository') . $this->getFolderPath(), 0777,
            true,
            true
        );

        File::put(
            config('path.paths.repository') . DIRECTORY_SEPARATOR . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->name . '.php',
            $file
        );
    }

    /**
     * @param ReflectionClass $model
     * @return PhpFile
     */
    private function generatePHPCodeByFile(ReflectionClass $model): PhpFile
    {
        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->namespace)
            ->addUse($this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface())
            ->addUse($model->getName());

        $class = $namespace
            ->addClass($this->name)
            ->addImplement($this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface());

        foreach ($this->methods as $method) {
            $methodFile = $class
                ->addMethod($method)
                ->setPublic();

            if ($this->option['dto']) {
                $this->getDataByDTOandFilter($method);

                $namespace
                    ->addUse($this->namespaceDTO)
                    ->addUse($this->namespaceFilter)
                    ->addUse(Collection::class);

                $methodFile
                    ->addComment('@inheritdoc')
                    ->addParameter('filter')
                    ->setType($this->namespaceFilter);

                $this->addBody($method, $methodFile);

                $this->addReturnByFile($method, $methodFile, $namespace);

            }
        }

        if ($this->option['dto']) {
            $this->addMethodToDTO($class, $model);
            $this->addMethodToDTOCollection($class);
        }

        return $file;
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
            ->addComment('@return ' . $this->className() . 'DTO');

        $method
            ->addParameter(Str::lcfirst($this->className()))
            ->setType($model->getName());

        $method
            ->setReturnType(config('path.namespaces.dto') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->className() . 'DTO');

        $method
            ->setBody('return new ' . $this->className() . 'DTO($' . Str::lcfirst($this->className()) . '->toArray());');
    }

    private function addMethodToDTOCollection(ClassType $class)
    {
        $methodCollection = $class
            ->addMethod('toDTOCollection')
            ->setPrivate();

        $methodCollection
            ->addComment('@param Collection' . ' $' . Str::lcfirst($this->className() . 's'));
        $methodCollection
            ->addComment('@return ' . $this->className() . 'DTOCollection');

        $methodCollection
            ->addParameter(Str::lcfirst($this->className()) . 's')
            ->setType(Collection::class);

        $methodCollection
            ->setReturnType(config('path.namespaces.dto') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->className() . 'DTOCollection');

        $methodCollection
            ->addBody('$list = [];')
            ->addBody('foreach ($' . Str::lcfirst($this->className()) . 's as $' . Str::lcfirst($this->className()) . ') {')
            ->addBody('$list[] = $this->toDTO($' . Str::lcfirst($this->className()) . ');')
            ->addBody('}')
            ->addBody('')
            ->addBody('return new ?(
            items: $list
            );', [new Literal($this->className() . 'DTOCollection')]);
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
                    ->addBody('return $this->toDTO($' . Str::lcfirst($this->className()) . ');');
                break;
            case 'show':
                $methodFile
                    ->addBody('return $this->toDTO(' . $this->className() . '::findOrFail($filter->getId()));');
                break;
            case 'update':
                $methodFile
                    ->addBody('$' . Str::lcfirst($this->className()) . ' = ' . $this->className() . '::findOrFail($filter->getId());')
                    ->addBody('return $' . Str::lcfirst($this->className()) . '->update($filter->toArray());');
                break;
            case 'delete':
                $methodFile
                    ->addBody('$' . Str::lcfirst($this->className()) . ' = ' . $this->className() . '::findOrFail($filter->getId());')
                    ->addBody('return $' . Str::lcfirst($this->className()) . '->delete();');
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
            case 'index':
                $methodInterface
                    ->addComment("@return {$this->classDTO}Collection")
                    ->setReturnType($this->namespaceDTO . 'Collection');
                $namespace->addUse($this->namespaceDTO . 'Collection');
                break;
            case 'create':
            case 'show':
                $methodInterface
                    ->addComment("@return {$this->classDTO}")
                    ->setReturnType($this->namespaceDTO);
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
                    ->setReturnType($this->namespaceDTO . 'Collection');
                $namespace->addUse($this->namespaceDTO . 'Collection');
                break;
            case 'create':
            case 'show':
                $methodInterface
                    ->setReturnType($this->namespaceDTO);
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
            config('path.rootPaths.repository') . $this->getFolderPath(), 0777,
            true,
            true
        );

        File::put(
            config('path.rootPaths.repository') . DIRECTORY_SEPARATOR . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameInterface() . '.php',
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
        return Str::beforeLast($this->laravel->getNamespace() . 'Contracts\\Repositories' . $this->argument, '\\');
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
