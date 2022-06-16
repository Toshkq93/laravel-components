<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Toshkq93\Components\Enums\MethodsByClassEnum;

class Service extends BaseServiceCreateClass
{
    /**
     * @return void
     */
    public function createBase(): void
    {
        if ($this->option['choice'] or !File::exists(config('component.paths.interface.service') . DIRECTORY_SEPARATOR . $this->getClassName() . config('component.prefix.service') . config('component.prefix.interface') . ".php")) {
            File::makeDirectory(
                config('component.paths.interface.service') ,
                0777,
                true,
                true
            );

            $this->generateInterface();
        }

        $pathBaseSerivce = config('component.paths.service') . DIRECTORY_SEPARATOR . config('component.base_name') . config('component.prefix.service') . '.php';

        if (!File::exists($pathBaseSerivce)) {
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
            ->addNamespace($this->getNamespaceBaseService())
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
            config('component.paths.service') . DIRECTORY_SEPARATOR . $this->getNameBaseService() . '.php',
            $file
        );
    }

    private function setReturn(string $method, PhpNamespace $namespace, Method $methodClass)
    {
        $namespaceOutputDto = $this->getNamespaceDtoOutputInterface() . DIRECTORY_SEPARATOR . config('component.prefix.dto.output') . config('component.prefix.interface');;

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
                    ->addComment('@return ' . config('component.prefix.dto.output') . config('component.prefix.interface'))
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
            ->addNamespace($this->getNamespaceServiceInterface());

        $namespace
            ->addInterface($this->getNameServiceInterface());

        File::put(
            config('component.paths.interface.service') . DIRECTORY_SEPARATOR . $this->getNameServiceInterface() . '.php',
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
        $namespaceInterface = $this->getNamespaceServiceInterface() . DIRECTORY_SEPARATOR . $this->getNameServiceInterface();
        $namespaceBaseService = $this->getNamespaceBaseService() . DIRECTORY_SEPARATOR . $this->getNameBaseService();
        $namespaceRepository = $this->getNamespaceRepositoryInterface() . DIRECTORY_SEPARATOR . $this->getNameRepositoryInterface();
        $nameRepository = Str::lcfirst($this->getClassName()) . config('component.prefix.repository');

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
            config('component.paths.service') . DIRECTORY_SEPARATOR . $this->getNameService() . '.php',
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

        $interface = DIRECTORY_SEPARATOR . $this->getNamespaceServiceInterface() . DIRECTORY_SEPARATOR . $this->getNameServiceInterface();
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
                $namespaceInput = 'App\\DTO\\Input\\Interfaces' . DIRECTORY_SEPARATOR . 'CreateInputDTOInterface';

                $namespace
                    ->addUse($namespaceInput);

                $methodClass
                    ->addComment('@param CreateInputDTOInterface $dto')
                    ->addParameter('dto')
                    ->setType($namespaceInput);

                break;
            case MethodsByClassEnum::UPDATE:
                $namespaceInput = 'App\\DTO\\Input\\Interfaces' . DIRECTORY_SEPARATOR . 'UpdateInputDTOInterface';

                $namespace
                    ->addUse($namespaceInput);

                $methodClass
                    ->addComment('@param UpdateInputDTOInterface $dto')
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
}
