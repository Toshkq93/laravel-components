<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpFile;
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
        $pathFolder = config('path.paths.controller') . $this->getFolderPath();

        if (!File::exists($pathFolder)) {
            File::makeDirectory(
                $pathFolder, 0777,
                true,
                true
            );
        }

        $this->namespaceBaseController = config('path.namespaces.controller');
        $nameBaseController = 'BaseController';

        $namespaceController = 'App\\Http\\Controllers\\Controller';

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->namespaceBaseController)
            ->addUse($namespaceController);

        $class = $namespace
            ->addClass('BaseController');

        $class->setExtends($namespaceController);

        File::put(config('path.paths.controller') . '\\' . $nameBaseController . '.php', $file);
    }

    /**
     * @return void
     */
    public function create(): void
    {
        $this->generatePHP();
    }

    private function generatePHP()
    {
        $namespaceClass = config('path.namespaces.controller') . $this->getFolderPath();
        $className = $this->className() . 'Controller';
        $classPath = config('path.paths.controller') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $className . '.php';
        $namespaceBaseController = config('path.namespaces.controller') . '\\BaseController';

        $controller = new PhpFile();

        $namespace = $controller
            ->addNamespace($namespaceClass)
            ->addUse($namespaceBaseController);

        $class = $namespace
            ->addClass($className)
            ->setExtends($namespaceBaseController);

        if ($this->options['service']) {
            $namespaceService = config('path.namespaces.contracts.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . 'i' . $this->className() . 'Service';

            $namespace
                ->addUse($namespaceService);

            $class
                ->addMethod('__construct')
                ->addPromotedParameter('service')
                ->setType($namespaceService)
                ->setPrivate();
        }

        foreach ($this->methods as $method) {
            $methodClass = $class
                ->addMethod($method)
                ->setPublic();
            $parameters = $methodClass
                ->addParameter('request');

            if (!$this->options['request']) {
                $methodClass
                    ->addComment('@param Request $request');

                $namespace
                    ->addUse(Request::class);

                $parameters
                    ->setType(Request::class);
            } else {
                $namespaceRequest = config('path.namespaces.request') . $this->getFolderPath() . DIRECTORY_SEPARATOR . Str::ucfirst($method) . $this->className() . 'Request';

                $methodClass
                    ->addComment('@param ' . Str::ucfirst($method) . $this->className() . 'Request $request');

                $namespace
                    ->addUse($namespaceRequest);

                $parameters
                    ->setType($namespaceRequest);
            }

            if ($this->options['resource'] && $this->options['service']) {
                $nameResource = Str::ucfirst($method) . $this->className();
                $namespaceResource = config('path.namespaces.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameResource;

                if ($this->options['request']) {
                    $string = '$this->service->' . $method;
                } else {
                    $string = '$this->service->' . $method;
                }

                if ($this->options['dto']) {
                    $string .= '($request->getFilterDTO())';
                } else {
                    $string .= '($request->all())';
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
                                . $string .
                                ');');
                        break;
                    case MethodsByClassEnum::DELETE:
                    case MethodsByClassEnum::UPDATE:
                        $namespace
                            ->addUse(JsonResponse::class);

                        $methodClass
                            ->addComment('@return JsonResponse')
                            ->setReturnType(JsonResponse::class)
                            ->addBody('return response()->json(' . $string . ');');
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
                                . $string .
                                ');');
                        break;
                }
            }
        }

        File::put($classPath, $controller);
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
