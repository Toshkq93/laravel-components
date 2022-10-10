<?php

namespace Toshkq93\Components\Services;

use App\DTO\Output\Interfaces\OutputDTOInterface;
use File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Illuminate\Foundation\Application;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use Toshkq93\Components\Enums\MethodsByClassEnum;

final class RepositoryService extends BaseServiceCreateClass
{
    private Application $laravel;
    private string $pathOutputDTO;

    public function __construct()
    {
        parent::__construct();
        $this->pathOutputDTO = $this->replacePathBySystem(config('component.paths.output'));
    }

    public function setLaravel(Application $laravel): void
    {
        $this->laravel = $laravel;
    }

    public function createBase(): void
    {
        $fileInterface = File::exists(config('component.paths.interface.repository') . DIRECTORY_SEPARATOR . $this->getNameRepositoryInterface() . ".php");

        if (!$fileInterface or $this->option['choice']) {
            File::makeDirectory(
                config('component.paths.interface.repository'),
                0777,
                true,
                true
            );

            $this->createInterface();
        }

        $pathBaseSerivce = config('component.paths.repository') . $this->getNameBaseRepository() . '.php';

        if (!File::exists($pathBaseSerivce)) {
            $this->createBaseRepository();
        }
    }

    public function create(): void
    {
        File::makeDirectory(
            config('component.paths.repository') . $this->getFolderPath(), 0777,
            true,
            true
        );

        $model = $this->laravel->make('App\\Models' . $this->argument);

        $classModels = new ReflectionClass($model);

        $this->generatePHPCodeByFile($classModels);
    }

    private function generatePHPCodeByFile(ReflectionClass $model): void
    {
        $file = new PhpFile();
        $namespaceInterface = $this->getNamespaceRepositoryInterface() . '\\' . $this->getNameRepositoryInterface();
        $namespaceBaseClass = $this->getNamespaceBaseRepository() . '\\' . $this->getNameBaseRepository();
        $classDto = $this->getClassName() . config('component.prefix.dto.output');
        $namespaceDto = $this->getNamespaceDtoOutput($this->pathOutputDTO) . '\\' . $classDto;

        $namespace = $file
            ->addNamespace($this->getNamespaceRepository())
            ->addUse($namespaceInterface)
            ->addUse($namespaceBaseClass)
            ->addUse($model->getName())
            ->addUse($namespaceDto);

        $class = $namespace
            ->addClass($this->getNameRepository())
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
            ->addParameter(Str::lcfirst($this->getClassName()))
            ->setType($model->getName());

        $construct->addBody('parent::__construct($' . Str::lcfirst($this->getClassName()) . ');');

        $file = File::put(
            config('component.paths.repository') . DIRECTORY_SEPARATOR . $this->getNameRepository() . '.php',
            $file
        );

        if ($file) {
            $this->createBind();
        }
    }

    private function createBind(): void
    {
        $provider = file(app_path('Providers\\AppServiceProvider.php'));

        $interface = '\\' . $this->getNamespaceRepositoryInterface() . '\\' . $this->getNameRepositoryInterface();
        $class = '\\' . $this->getNamespaceRepository() . '\\' . $this->getNameRepository();

        $lineBind = "\t\t" . '$this->app->bind(' . $interface . '::class, ' . $class . '::class);' . PHP_EOL;

        $searchMethod = 'public function boot()';

        foreach ($provider as $key => $line) {
            if (Str::contains($line, $searchMethod)) {
                array_splice($provider, $key + 2, 0, $lineBind);
            }
        }

        File::put(app_path('Providers\\AppServiceProvider.php'), $provider);
    }

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
                    ->addBody("\t" . '$this->getQuery()->create($dto->toArray())')
                    ->addBody(');');

                break;
            case MethodsByClassEnum::SHOW:
                $methodFile
                    ->addBody('return new (static::dtoName())(')
                    ->addBody("\t" . '$this->find($id)->toArray()')
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
            ->setComment('@return string')
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
            config('component.paths.repository') . DIRECTORY_SEPARATOR . $this->getNameBaseRepository() . '.php',
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
                    ->addUse(OutputDTOInterface::class);

                $methodClass
                    ->addComment('@return OutputDTOInterface')
                    ->setReturnType(OutputDTOInterface::class);

                break;
            case MethodsByClassEnum::UPDATE:
            case MethodsByClassEnum::DELETE:
                $methodClass
                    ->addComment('@return bool')
                    ->setReturnType('bool');
                break;
        }
    }

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

    private function createInterface(): void
    {
        $interface = new PhpFile();

        $namespace = $interface
            ->addNamespace($this->getNamespaceRepositoryInterface());

        $namespace
            ->addInterface($this->getNameRepositoryInterface());

        File::put(
            config('component.paths.interface.repository') . DIRECTORY_SEPARATOR . $this->getNameRepositoryInterface() . '.php',
            $interface
        );
    }

    private function getDataByDTO(string $method, PhpNamespace $namespace, Method $methodClass): void
    {
        switch ($method) {
            case MethodsByClassEnum::CREATE:
                $nameInputDto = MethodsByClassEnum::CREATE_METHOD . config('component.prefix.dto.input') . config('component.prefix.interface');
                $namespaceInputDto = $this->getNamespaceDtoInputInterface() . '\\' . $nameInputDto;

                $namespace
                    ->addUse($namespaceInputDto);

                $methodClass
                    ->addComment('@param ' . $nameInputDto . ' $dto')
                    ->addParameter('dto')
                    ->setType($namespaceInputDto);

                break;
            case MethodsByClassEnum::UPDATE:
                $nameInputDto = MethodsByClassEnum::UPDATE_METHOD . config('component.prefix.dto.input') . config('component.prefix.interface');
                $namespaceInputDto = $this->getNamespaceDtoInputInterface() . '\\' . $nameInputDto;

                $namespace
                    ->addUse($namespaceInputDto);

                $methodClass
                    ->addComment('@param ' . $nameInputDto . ' $dto')
                    ->addParameter('dto')
                    ->setType($namespaceInputDto);

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
}
