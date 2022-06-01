<?php

namespace Toshkq93\Components\Services;

use App\DTO\Output\Interfaces\OutputInterface;
use File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Illuminate\Foundation\Application;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use Toshkq93\Components\Enums\MethodsByClassEnum;

class RepositoryService
{
    /** @var string */
    private string $argument;

    /** @var Application */
    private Application $laravel;

    /** @var array|null */
    private null|array $option;

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
    public function createBase(): void
    {
        $fileInterface = File::exists(config('component.paths.rootPaths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . "{$this->className()}RepositoryInterface.php");

        if (!$fileInterface or $this->option['choice']) {
            File::makeDirectory(
                config('component.paths.rootPaths.repository') . $this->getFolderPath(), 0777,
                true,
                true
            );

            $this->createInterface();
        }

        $pathBaseSerivce = config('component.paths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . config('component.baseFile.repository') . '.php';
        if (!File::exists($pathBaseSerivce))
        {
            $this->createBaseRepository();
        }
    }

    /**
     * @return void
     */
    public function create(): void
    {
        File::makeDirectory(
            config('component.paths.repository') . $this->getFolderPath(), 0777,
            true,
            true
        );

        $this->name = Str::ucfirst($this->className()) . 'Repository';
        $this->namespace = config('component.namespaces.repository') . $this->getFolderPath();
        $model = $this->laravel->make('App\\Models' . $this->argument);

        $classModels = new ReflectionClass($model);

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
        $namespaceBaseClass = $this->getNamespaceBaseRepository() . DIRECTORY_SEPARATOR . $this->getNameBaseRepository();
        $classDto = $this->className() . 'Output';
        $namespaceDto = config('component.namespaces.output') . DIRECTORY_SEPARATOR . $classDto;

        $namespace = $file
            ->addNamespace($this->namespace)
            ->addUse($namespaceInterface)
            ->addUse($namespaceBaseClass)
            ->addUse($model->getName())
            ->addUse($namespaceDto);

        $class = $namespace
            ->addClass($this->name)
            ->setFinal()
            ->setExtends($namespaceBaseClass)
            ->addImplement($namespaceInterface);

        $construct = $class
            ->addMethod('__construct');

        $class
            ->addMethod('dtoName')
            ->setProtected()
            ->setStatic()
            ->addComment('@return string')
            ->setReturnType('string')
            ->addBody('return ' . $classDto . '::class;');

        $construct
            ->addParameter(Str::lcfirst($this->className()))
            ->setType($model->getName());

        $construct->addBody('parent::__construct($' . Str::lcfirst($this->className()) . ');');

        $file = File::put(
            config('component.paths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->name . '.php',
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
                array_splice($provider, $key + 2, 0, $lineBind);
            }
        }

        File::put(app_path('Providers\\AppServiceProvider.php'), $provider);
    }

    /**
     * @param string $method
     * @param Method $methodFile
     * @return void
     */
    private function addBody(string $method, Method $methodFile): void
    {
        switch ($method) {
            case MethodsByClassEnum::ALL:
                $methodFile
                    ->addBody('return $this->getQuery()->get()')
                    ->addBody("\t" . '->map(fn(Model $model) => new (static::dtoName())(')
                    ->addBody("\t\t" . '$model->toArray())')
                    ->addBody("\t" . ');');

                break;
            case MethodsByClassEnum::CREATE:
                $methodFile
                    ->addBody('return new (static::dtoName())(')
                    ->addBody("\t" .'$this->getQuery()->create($dto->toArray())')
                    ->addBody(');');

                break;
            case MethodsByClassEnum::SHOW:
                $methodFile
                    ->addBody('return new (static::dtoName())(')
                    ->addBody("\t" .'$this->find($id)->toArray()')
                    ->addBody(');');

                break;
            case MethodsByClassEnum::UPDATE:
                $methodFile
                    ->addBody('return $this->find($id)->update($dto->toArray());');
                break;
            case MethodsByClassEnum::DELETE:
                $methodFile
                    ->addBody('return $this->find($id)->delete();');
                break;
        }
    }

    /**
     * @return void
     */
    private function createBaseRepository(): void
    {
        $baseRepository = new PhpFile();

        $namespace = $baseRepository
            ->addNamespace($this->getNamespaceBaseRepository())
            ->addUse(Model::class);

        $class = $namespace
            ->addClass($this->getNameBaseRepository())
            ->setAbstract();

        $class
            ->addMethod('__construct')
            ->addPromotedParameter('model')
            ->setType(Model::class)
            ->setProtected();

        $class
            ->addMethod('dtoName')
            ->setProtected()
            ->setAbstract()
            ->setStatic()
            ->setReturnType('string');


        foreach (MethodsByClassEnum::REPOSITORY_METHODS as $method) {
            $methodFile = $class
                ->addMethod($method)
                ->setPublic();

            if ($this->option['dto']) {
                $this->getDataByDTO($method, $namespace, $methodFile);
                $this->addBody($method, $methodFile);
                $this->setReturn($method, $namespace, $methodFile);
            }
        }

        $this->createMethodsHelpers($class, $namespace);

        File::put(
            config('component.paths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . config('component.baseFile.repository') . '.php',
            $baseRepository
        );
    }

    private function setReturn(string $method, PhpNamespace $namespace, Method $methodClass)
    {
        switch ($method) {
            case MethodsByClassEnum::ALL:
                $namespace
                    ->addUse(Collection::class);

                $methodClass
                    ->addComment('@return Collection')
                    ->setReturnType(Collection::class);

                break;
            case MethodsByClassEnum::CREATE:
            case MethodsByClassEnum::SHOW:
                $namespace
                    ->addUse(OutputInterface::class);

                $methodClass
                    ->addComment('@return OutputInterface')
                    ->setReturnType(OutputInterface::class);

                break;
            case MethodsByClassEnum::UPDATE:
            case MethodsByClassEnum::DELETE:
                $methodClass
                    ->addComment('@return bool')
                    ->setReturnType('bool');
                break;
        }
    }

    /**
     * @param ClassType $class
     * @param PhpNamespace $namespace
     * @return void
     */
    private function createMethodsHelpers(ClassType $class, PhpNamespace $namespace): void
    {
        $namespace
            ->addUse(Builder::class);

        $methodQuery = $class
            ->addMethod('getQuery')
            ->setProtected();
        $methodQuery
            ->addComment('@return Builder')
            ->addBody('return $this->model::query();')
            ->setReturnType(Builder::class);

        $methodFind = $class
            ->addMethod('find')
            ->setReturnType('?' . Model::class)
            ->setProtected();

        $methodFind
            ->addComment('@param int $id')
            ->addComment('@return null|Model')
            ->addParameter('id')
            ->setType('int');

        $methodFind
            ->addBody('return $this->getQuery()->findOrFail($id);');
    }

    /**
     * @return void
     */
    private function createInterface(): void
    {
        $interface = new PhpFile();

        $namespace = $interface
            ->addNamespace($this->getNamespaceInterface());

        $namespace
            ->addInterface($this->getNameInterface());

        File::put(
            config('component.paths.rootPaths.repository') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameInterface() . '.php',
            $interface
        );
    }

    /**
     * @param $method
     * @return void
     */
    private function getDataByDTO(string $method, PhpNamespace $namespace, Method $methodClass): void
    {
        switch ($method) {
            case MethodsByClassEnum::CREATE:
                $namespaceInput = config('component.namespaces.interface.dto.input') . $this->getFolderPath() . DIRECTORY_SEPARATOR . 'CreateInputInterface';

                $namespace
                    ->addUse($namespaceInput);

                $methodClass
                    ->addComment('@param CreateInputInterface $dto')
                    ->addParameter('dto')
                    ->setType($namespaceInput);

                break;
            case MethodsByClassEnum::UPDATE:
                $namespaceInput = config('component.namespaces.interface.dto.input') . $this->getFolderPath() . DIRECTORY_SEPARATOR . 'UpdateInputInterface';

                $namespace
                    ->addUse($namespaceInput);

                $methodClass
                    ->addComment('@param UpdateInputInterface $dto')
                    ->addParameter('dto')
                    ->setType($namespaceInput);

                $methodClass
                    ->addComment('@param int $id')
                    ->addParameter('id')
                    ->setType('int');

                break;
            case MethodsByClassEnum::SHOW:
            case MethodsByClassEnum::DELETE:
                $methodClass
                    ->addComment('@param int $id')
                    ->addParameter('id')
                    ->setType('int');
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
        return config('component.namespaces.interface.repository') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    private function getNameInterface(): string
    {
        return Str::ucfirst($this->className()) . 'RepositoryInterface';
    }

    /**
     * @return string
     */
    private function getNamespaceBaseRepository(): string
    {
        return config('component.namespaces.base.repository');
    }

    private function getNameBaseRepository(): string
    {
        return config('component.baseFile.repository');
    }

    /**
     * @return string
     */
    private function className(): string
    {
        return class_basename($this->argument);
    }
}
