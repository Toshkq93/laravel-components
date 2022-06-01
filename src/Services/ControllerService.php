<?php

namespace Toshkq93\Components\Services;

use App\Http\Controllers\Controller;
use File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Toshkq93\Components\Enums\MethodsByClassEnum;

class ControllerService
{
    /** @var string */
    private string $argument;

    /** @var array|null */
    private null|array $options;

    /** @var string */
    private string $namespaceBaseController;

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
        $baseController = config('component.paths.controller') . config('component.baseFile.controller') . '.php';

        if (!File::exists($baseController)) {
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
    }

    /**
     * @return void
     */
    public function create(): void
    {
        $namespaceClass = config('component.namespaces.controller') . $this->getFolderPath();
        $nameClass = $this->className() . 'Controller';
        $namespaceBaseController = config('component.namespaces.base.controller') . DIRECTORY_SEPARATOR . config('component.baseFile.controller');

        $fileController = new PhpFile();

        $namespace = $fileController
            ->addNamespace($namespaceClass)
            ->addUse($namespaceBaseController);

        $class = $namespace
            ->addClass($nameClass)
            ->setFinal()
            ->setExtends($namespaceBaseController);

        if ($this->options['service']) {
            $this->addService($namespace, $class);
        }

        foreach (MethodsByClassEnum::CONTROLLER_METHODS as $method) {
            $methodClass = $class
                ->addMethod($method)
                ->setPublic();

            $this->generateInputParameters($methodClass, $namespace, $method);

            if ($this->options['resource'] && $this->options['service']) {
                $this->generateBody($method, $namespace, $methodClass);
            }
        }

        $file = File::put(
            config('component.paths.controller') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameClass . '.php',
            $fileController
        );

        if ($file) {
            $this->createRoute($namespaceClass, $nameClass);
        }
    }

    /**
     * @param string $namespaceClass
     * @param string $className
     * @return void
     */
    private function createRoute(string $namespaceClass, string $className): void
    {
        $fileName = Str::afterLast(config('component.route_path'), '\\');

        if (!File::exists(config('component.route_path'))) {
            $folder = base_path('routes') . DIRECTORY_SEPARATOR . Str::afterLast(dirname(config('component.route_path')), 'routes\\');

            File::makeDirectory($folder);

            $fileRoute = new PhpFile();
            $fileRoute->getClasses();
            $fileRoute->addUse(Route::class);
            $printer = new PsrPrinter();
            $data = $printer->printFile($fileRoute);

            File::put($folder . DIRECTORY_SEPARATOR . $fileName, $data);

            $searchMethod = '$this->routes(function () {';
            $routeServiceProvider = file(app_path('Providers') . DIRECTORY_SEPARATOR . 'RouteServiceprovider.php');
            $prefix = 'api/' . Str::afterLast(dirname(config('component.route_path')), 'routes\\');

            $lineAdd = "\n\t\t\tRoute::prefix('" . $prefix . "')" . PHP_EOL . "\t\t\t\t->middleware('api')" . PHP_EOL . "\t\t\t\t" . '->namespace($this->namespace)' . PHP_EOL . "\t\t\t\t->group(base_path('routes\\" . Str::afterLast(config('component.route_path'), 'routes\\') . "'));" . PHP_EOL;

            foreach ($routeServiceProvider as $key => $line) {
                if (Str::contains($line, $searchMethod)) {
                    array_splice($routeServiceProvider, $key + 1, 0, $lineAdd);
                }
            }

            File::put(app_path('Providers\\RouteServiceProvider.php'), $routeServiceProvider);
        }

        $fileRoute = file(config('component.route_path'));

        $searchLine = Route::class;
        $class = $namespaceClass . DIRECTORY_SEPARATOR . $className;
        $routeUrl = Str::snake(Str::plural($this->className()), '-');

        if (Str::contains($fileName, 'api')) {
            $lineRoute = "\n" . 'Route::apiResource(' . "'/" . $routeUrl . "', \\" . $class . "::class)->params(['" . $routeUrl . "' => 'id']);" . PHP_EOL;
        } else {
            $lineRoute = "\n" . 'Route::resource(' . "'/" . $routeUrl . "', \\" . $class . "::class);" . PHP_EOL;
        }

        foreach ($fileRoute as $key => $line) {
            if (Str::contains($line, $searchLine)) {
                array_splice($fileRoute, $key + 1, 0, $lineRoute);
            }
        }

        File::put(config('component.route_path'), $fileRoute);
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
        $nameResource = $this->className() . 'Resource';
        $namespaceResource = config('component.namespaces.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameResource;
        $nameResourceCollection = $this->className() . 'Collection';
        $namespaceResourceCollection = config('component.namespaces.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameResourceCollection;
        $nameDTO = Str::ucfirst($method === MethodsByClassEnum::STORE ? MethodsByClassEnum::CREATE : MethodsByClassEnum::UPDATE) . $this->className() . 'Input';
        $namespaceDTO = config('component.namespaces.input') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameDTO;

        $defaultId = 'id';

        $namespace
            ->addUse(JsonResponse::class);


        switch ($method) {
            case MethodsByClassEnum::INDEX:
                $body = 'return new ' . $nameResourceCollection . '(';

                if ($this->options['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::ALL . '());';
                } else {
                    $body = '';
                }

                $namespace
                    ->addUse($namespaceResourceCollection);

                $methodClass
                    ->addComment('@return ' . $nameResourceCollection)
                    ->setReturnType($namespaceResourceCollection)
                    ->addBody($body);
                break;
            case MethodsByClassEnum::STORE:
                $body = '';

                if ($this->options['dto']) {
                    $namespace
                        ->addUse($namespaceDTO);
                    $body = '$dto = new ' . $nameDTO . '($request->validated());' . PHP_EOL . "\n";
                }

                $body .= 'return new ' . $nameResource . '(';

                if ($this->options['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::CREATE . '(';

                    if ($this->options['dto']) {

                        $body .= '$dto));';
                    } else {
                        $body .= '$request->validated()));';
                    }
                } else {
                    $body = '';
                }

                $namespace
                    ->addUse($namespaceResource);

                $methodClass
                    ->addComment('@return ' . $nameResource)
                    ->setReturnType($namespaceResource)
                    ->addBody($body);
                break;
            case MethodsByClassEnum::SHOW:
                $body = 'return new ' . $nameResource . '(';

                if ($this->options['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::SHOW . '($' . $defaultId . '));';
                } else {
                    $body = '';
                }

                $namespace
                    ->addUse($namespaceResource);

                $methodClass
                    ->addComment('@return ' . $nameResource)
                    ->setReturnType($namespaceResource)
                    ->addBody($body);
                break;
            case MethodsByClassEnum::UPDATE:
                $body = '';

                if ($this->options['dto']) {
                    $namespace
                        ->addUse($namespaceDTO);
                    $body .= '$dto = new ' . $nameDTO . '($request->validated());' . PHP_EOL . "\n";
                }

                $body .= 'return response()->json(';

                if ($this->options['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::UPDATE . '(';

                    if ($this->options['dto']) {
                        $body .= '$dto, $' . $defaultId . '));';
                    } else {
                        $body .= '$request->validated(), $' . $defaultId . '));';
                    }
                } else {
                    $body = '';
                }

                $methodClass
                    ->addComment('@return JsonResponse')
                    ->setReturnType(JsonResponse::class)
                    ->addBody($body);

                break;
            case MethodsByClassEnum::DELETE:
                $body = 'return response()->json(';

                if ($this->options['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::DELETE . '($' . $defaultId . '));';
                } else {
                    $body = '';
                }

                $methodClass
                    ->addComment('@return JsonResponse')
                    ->setReturnType(JsonResponse::class)
                    ->addBody($body);
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
        $namespaceService = config('component.namespaces.interface.service') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->className() . 'ServiceInterface';

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
        string       $method
    ): void
    {
        $defaultParameter = 'id';

        switch ($method) {
            case MethodsByClassEnum::STORE:
            case MethodsByClassEnum::UPDATE:
                $prefixRequest = Str::ucfirst($method == MethodsByClassEnum::STORE ? MethodsByClassEnum::CREATE : MethodsByClassEnum::UPDATE);
                $namespaceRequest = config('component.namespaces.request') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $prefixRequest . $this->className() . 'Request';

                $parameter = $methodClass
                    ->addParameter('request');

                if (!$this->options['request']) {
                    $methodClass
                        ->addComment('@param Request $request');

                    $namespace
                        ->addUse(Request::class);

                    $parameter
                        ->setType(Request::class);
                } else {

                    $methodClass
                        ->addComment('@param ' . $prefixRequest . $this->className() . 'Request $request');

                    $namespace
                        ->addUse($namespaceRequest);

                    $parameter
                        ->setType($namespaceRequest);
                }
                if ($method === MethodsByClassEnum::UPDATE) {
                    $methodClass
                        ->addComment('@param int $' . $defaultParameter)
                        ->addParameter($defaultParameter)
                        ->setType('int');
                }
                break;
            case MethodsByClassEnum::SHOW:
            case MethodsByClassEnum::DELETE:
                $methodClass
                    ->addComment('@param int $' . $defaultParameter)
                    ->addParameter($defaultParameter)
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
    private function className(): string
    {
        return class_basename($this->argument);
    }
}
