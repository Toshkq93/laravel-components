<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Toshkq93\Components\Enums\MethodsByClassEnum;

class Service
{
    /** @var string */
    private string $argument;

    /** @var array|null */
    private null|array $option;

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
    public function createBase(): void
    {
        if ($this->option['choice'] or !File::exists(config('component.paths.rootPaths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . "{$this->className()}ServiceInterface.php")) {
            File::makeDirectory(
                config('component.paths.rootPaths.service') . $this->getFolderPath(), 0777,
                true,
                true
            );

            $this->generateInterface();
        }

        $pathBaseSerivce = config('component.paths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . config('component.baseFile.service') . '.php';
        if (!File::exists($pathBaseSerivce))
        {
            $this->generateBaseService();
        }
    }

    /**
     * @return void
     */
    private function generateBaseService(): void
    {
        $namespaceBaseRepository = $this->getNamespaceBaseRepository() . DIRECTORY_SEPARATOR . $this->getNameBaseRepository();

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->getNamespaceBaseSerive())
            ->addUse($namespaceBaseRepository);

        $baseClass = $namespace
            ->addClass($this->getNameBaseService());

        $construct = $baseClass
            ->addMethod('__construct');

        $construct
            ->addPromotedParameter('repository')
            ->setProtected()
            ->setType($namespaceBaseRepository);

        foreach (MethodsByClassEnum::SERVICE_METHODS as $method) {
            $methodFile = $baseClass
                ->addMethod($method)
                ->setPublic();

            if ($this->option['dto']) {
                $this->getDataInputClass($method, $namespace, $methodFile);

                if ($this->option['repository']) {
                    $this->setBody($method, $methodFile);
                }

                $this->setReturn($method, $namespace, $methodFile);
            }
        }

        File::put(
            config('component.paths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameBaseService() . '.php',
            $file
        );
    }

    private function setReturn(string $method, PhpNamespace $namespace, Method $methodClass)
    {
        $namespaceOutputDto = config('component.namespaces.interface.dto.output') . DIRECTORY_SEPARATOR . 'OutputInterface';

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
                    ->addUse($namespaceOutputDto);

                $methodClass
                    ->addComment('@return OutputInterface')
                    ->setReturnType($namespaceOutputDto);

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
     * @return void
     */
    private function generateInterface(): void
    {
        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->getNamespaceInterface());

        $namespace
            ->addInterface($this->getNameInterface());

        File::put(
            config('component.paths.rootPaths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameInterface() . '.php',
            $file
        );
    }

    /**
     * @return void
     */
    public function create(): void
    {
        File::makeDirectory(
            config('component.paths.service') . $this->getFolderPath(), 0777,
            true,
            true
        );

        $this->generatePHPCodeByFile();
    }

    /**
     * @return void
     */
    private function generatePHPCodeByFile(): void
    {
        $namespaceInterface = $this->getNamespaceInterface() . DIRECTORY_SEPARATOR . $this->getNameInterface();
        $namespaceBaseService = $this->getNamespaceBaseSerive() . DIRECTORY_SEPARATOR . $this->getNameBaseService();
        $namespaceRepository = $this->getNamespaceRepositoryInterface() . DIRECTORY_SEPARATOR . $this->getNameRepositoryInterface();
        $nameRepository = Str::lcfirst($this->className()) . 'Repository';

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->getNamespaceService())
            ->addUse($namespaceInterface)
            ->addUse($namespaceBaseService)
            ->addUse($namespaceRepository);

        $class = $namespace
            ->addClass($this->getNameService())
            ->setFinal()
            ->setExtends($namespaceBaseService)
            ->addImplement($namespaceInterface);

        $construct = $class
            ->addMethod('__construct');

        $construct
            ->addParameter($nameRepository)
            ->setType($namespaceRepository);

        $construct
            ->addBody('parent::__construct($' . $nameRepository . ');');

        $file = File::put(
            config('component.paths.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameService() . '.php',
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
        $class = DIRECTORY_SEPARATOR . $this->getNamespaceService() . DIRECTORY_SEPARATOR . $this->getNameService();

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
     * @param $method
     * @return void
     */
    private function getDataInputClass(string $method, PhpNamespace $namespace, Method $methodClass): void
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
     * @param string $method
     * @param Method $methodFile
     * @return void
     */
    private function setBody(string $method, Method $methodFile): void
    {
        switch ($method) {
            case MethodsByClassEnum::ALL:
                $methodFile
                    ->addBody('return $this->repository->' . MethodsByClassEnum::ALL . '();');
                break;
            case MethodsByClassEnum::CREATE:
                $methodFile
                    ->addBody('return $this->repository->' . MethodsByClassEnum::CREATE . '($dto);');
                break;
            case MethodsByClassEnum::SHOW:
                $methodFile
                    ->addBody('return $this->repository->' . MethodsByClassEnum::SHOW . '($id);');
                break;
            case MethodsByClassEnum::UPDATE:
                $methodFile
                    ->setReturnType('bool')
                    ->addBody('return $this->repository->' . MethodsByClassEnum::UPDATE . '($dto, $id);');
                break;
            case MethodsByClassEnum::DELETE:
                $methodFile
                    ->setReturnType('bool')
                    ->addBody('return $this->repository->' . MethodsByClassEnum::DELETE . '($id);');
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
        return config('component.namespaces.interface.service') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    private function getNameInterface(): string
    {
        return Str::ucfirst($this->className()) . 'ServiceInterface';
    }

    /**
     * @return string
     */
    private function getNamespaceService(): string
    {
        return config('component.namespaces.service') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    private function getNameService(): string
    {
        return Str::ucfirst($this->className()) . 'Service';
    }

    /**
     * @return string
     */
    private function getNamespaceBaseSerive(): string
    {
        return config('component.namespaces.base.service');
    }

    /**
     * @return string
     */
    private function getNameBaseService(): string
    {
        return config('component.baseFile.service');
    }

    /**
     * @return string
     */
    private function getNamespaceRepositoryInterface(): string
    {
        return config('component.namespaces.interface.repository') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    private function getNameRepositoryInterface(): string
    {
        return Str::ucfirst($this->className()) . 'RepositoryInterface';
    }

    private function getNameBaseRepository(): string
    {
        return config('component.baseFile.repository');
    }

    private function getNamespaceBaseRepository(): string
    {
        return config('component.namespaces.base.repository');
    }

    /**
     * @return string
     */
    private function className(): string
    {
        return class_basename($this->argument);
    }
}
