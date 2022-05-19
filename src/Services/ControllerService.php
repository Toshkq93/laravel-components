<?php

namespace Toshkq93\Components\Services;

use App\Http\Controllers\Controller;
use File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Toshkq93\Components\Enums\MethodsByClassEnum;

class ControllerService
{
    /** @var string */
    private string $argument;

    /** @var array|null */
    private null|array $options;

    /** @var string */
    private string $namespaceBaseController;

    /** @var array|string[] */
    private array $methods = [
        'index',
        'create',
        'show',
        'update',
        'delete'
    ];

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
    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return void
     */
    public function createBaseController(): void
    {
        $pathFolder = config('component.paths.controller') . $this->getFolderPath();

        if (!File::exists($pathFolder)) {
            File::makeDirectory(
                $pathFolder, 0777,
                true,
                true
            );
        }

        $this->namespaceBaseController = config('component.namespaces.controller');

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->namespaceBaseController)
            ->addUse(Controller::class);

        $class = $namespace
            ->addClass(config('component.baseFile.controller'));

        $class->setExtends(Controller::class);

        File::put(config('component.paths.controller') . '\\' . config('component.baseFile.controller') . '.php', $file);
    }

    /**
     * @return void
     */
    public function create(): void
    {
        $namespaceClass = config('component.namespaces.controller') . $this->getFolderPath();
        $className = $this->className() . 'Controller';
        $classPath = config('component.paths.controller') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $className . '.php';
        $namespaceBaseController = config('component.namespaces.base.controller') . DIRECTORY_SEPARATOR . config('component.baseFile.controller');

        $controller = new PhpFile();

        $namespace = $controller
            ->addNamespace($namespaceClass)
            ->addUse($namespaceBaseController);

        $class = $namespace
            ->addClass($className)
            ->setExtends($namespaceBaseController);

        if ($this->options['service']) {
            $this->addService($namespace, $class);
        }

        foreach ($this->methods as $method) {
            $methodClass = $class
                ->addMethod($method)
                ->setPublic();
            $parameter = $methodClass
                ->addParameter('request');

            $this->generateInputParameters($methodClass, $namespace, $parameter, $method);

            if ($this->options['resource'] && $this->options['service']) {
                $this->generateBody($method, $namespace, $methodClass);
            }
        }

        File::put($classPath, $controller);
    }

    /**
     * @param string $method
     * @param PhpNamespace $namespace
     * @param Method $methodClass
     * @return void
     */
    private function generateBody(
        string       $method,
        PhpNamespace $namespace,
        Method       $methodClass
    ): void
    {
        $nameResource = Str::ucfirst($method) . $this->className();
        $namespaceResource = config('component.namespaces.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameResource;

        if ($this->options['request']) {
            $body = '$this->service->' . $method;
        } else {
            $body = '$this->service->' . $method;
        }

        if ($this->options['dto']) {
            $body .= '($request->getFilterDTO())';
        } else {
            $body .= '($request->all())';
        }

        switch ($method) {
            case MethodsByClassEnum::INDEX:
                $nameResource .= 'Collection';
                $namespaceResource .= 'Collection';
                $namespace
                    ->addUse($namespaceResource);

                $methodClass
                    ->addComment('@return ' . $nameResource)
                    ->setReturnType($namespaceResource)
                    ->addBody('return new ' . $nameResource . '('
                        . $body .
                        ');');
                break;
            case MethodsByClassEnum::DELETE:
            case MethodsByClassEnum::UPDATE:
                $namespace
                    ->addUse(JsonResponse::class);

                $methodClass
                    ->addComment('@return JsonResponse')
                    ->setReturnType(JsonResponse::class)
                    ->addBody('return response()->json(' . $body . ');');
                break;
            case MethodsByClassEnum::CREATE:
            case MethodsByClassEnum::SHOW:
                $nameResource .= 'Resource';
                $namespaceResource .= 'Resource';
                $namespace
                    ->addUse($namespaceResource);

                $methodClass
                    ->addComment('@return ' . $nameResource)
                    ->setReturnType($namespaceResource)
                    ->addBody('return new ' . $nameResource . '('
                        . $body .
                        ');');
                break;
        }
    }

    /**
     * @param PhpNamespace $namespace
     * @param ClassType $class
     * @return void
     */
    private function addService(PhpNamespace $namespace, ClassType $class): void
    {
        $namespaceService = config('component.namespaces.contracts.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . 'i' . $this->className() . 'Service';

        $namespace
            ->addUse($namespaceService);

        $class
            ->addMethod('__construct')
            ->addPromotedParameter('service')
            ->setType($namespaceService)
            ->setPrivate();
    }

    /**
     * @param Method $methodClass
     * @param PhpNamespace $namespace
     * @param Parameter $parameter
     * @param string $method
     * @return void
     */
    private function generateInputParameters(
        Method       $methodClass,
        PhpNamespace $namespace,
        Parameter    $parameter,
        string       $method
    ): void
    {
        if (!$this->options['request']) {
            $methodClass
                ->addComment('@param Request $request');

            $namespace
                ->addUse(Request::class);

            $parameter
                ->setType(Request::class);
        } else {
            $namespaceRequest = config('component.namespaces.request') . $this->getFolderPath() . DIRECTORY_SEPARATOR . Str::ucfirst($method) . $this->className() . 'Request';

            $methodClass
                ->addComment('@param ' . Str::ucfirst($method) . $this->className() . 'Request $request');

            $namespace
                ->addUse($namespaceRequest);

            $parameter
                ->setType($namespaceRequest);
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
    private function className(): string
    {
        return class_basename($this->argument);
    }
}
