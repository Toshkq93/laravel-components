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

class ControllerService extends BaseServiceCreateClass
{
    private array $primaryKey;

    /**
     * @param array $primaryKey
     * @return void
     */
    public function setPrimaryKey(array $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return void
     */
    public function create(): void
    {
        File::makeDirectory(
            config('component.paths.controller'), 0777,
            true,
            true
        );

        $fileController = new PhpFile();

        $namespace = $fileController
            ->addNamespace($this->getNamespaceController())
            ->addUse(Controller::class);

        $class = $namespace
            ->addClass($this->getNameController())
            ->setFinal()
            ->setExtends(Controller::class);

        if ($this->option['service']) {
            $this->addService($namespace, $class);
        }

        foreach (MethodsByClassEnum::CONTROLLER_METHODS as $method) {
            $methodClass = $class
                ->addMethod($method)
                ->setPublic();

            $this->generateInputParameters($methodClass, $namespace, $method);

            if ($this->option['resource'] && $this->option['service']) {
                $this->generateBody($method, $namespace, $methodClass);
            }
        }

        $file = File::put(
            config('component.paths.controller') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->getNameController() . '.php',
            $fileController
        );

        if ($file) {
            $this->createRoute();
        }
    }

    /**
     * @return void
     */
    private function createRoute(): void
    {
        $fileName = Str::afterLast(config('component.route_path'), '\\');

        if (!File::exists(config('component.route_path'))) {
            $folder = base_path('routes') . DIRECTORY_SEPARATOR . Str::afterLast(dirname(config('component.route_path')), 'routes\\');

            File::makeDirectory($folder);

            $fileRoute = new PhpFile();
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
        $class = $this->getNamespaceController() . DIRECTORY_SEPARATOR . $this->getNameController();
        $routeUrl = Str::snake(Str::plural($this->getClassName()), '-');
        $parameter = Str::lower(Str::plural($this->getClassName()));

        if (Str::contains($fileName, 'api')) {
            $lineRoute = "\n" . 'Route::apiResource(' . "'/" . $routeUrl . "', \\" . $class . "::class)->parameters(['" . $parameter . "' => 'id']);" . PHP_EOL;
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
        $nameResource = $this->getClassName() . config('component.prefix.resource.resource');
        $namespaceResource = $this->getNamespaceResource() . DIRECTORY_SEPARATOR . $nameResource;
        $nameResourceCollection = $this->getClassName() . config('component.prefix.resource.collection');
        $namespaceResourceCollection = $this->getNamespaceResource() . DIRECTORY_SEPARATOR . $nameResourceCollection;
        $nameDTO = Str::ucfirst($method === MethodsByClassEnum::STORE ? MethodsByClassEnum::CREATE : MethodsByClassEnum::UPDATE) . $this->getClassName() . config('component.prefix.dto.input');
        $namespaceDTO = $this->getNamespaceDtoInput() . DIRECTORY_SEPARATOR . $nameDTO;

        $namespace
            ->addUse(JsonResponse::class);

        switch ($method) {
            case MethodsByClassEnum::INDEX:
                $body = 'return new ' . $nameResourceCollection . '(';

                if ($this->option['service']) {
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

                if ($this->option['dto']) {
                    $namespace
                        ->addUse($namespaceDTO);
                    $body = '$dto = new ' . $nameDTO . '($request->validated());' . PHP_EOL . "\n";
                }

                $body .= 'return new ' . $nameResource . '(';

                if ($this->option['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::CREATE . '(';

                    if ($this->option['dto']) {
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

                if ($this->option['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::SHOW . '($id));';
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

                if ($this->option['dto']) {
                    $namespace
                        ->addUse($namespaceDTO);
                    $body .= '$dto = new ' . $nameDTO . '($request->validated());' . PHP_EOL . "\n";
                }

                $body .= 'return response()->json(';

                if ($this->option['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::UPDATE . '(';

                    if ($this->option['dto']) {
                        $body .= '$dto, $id));';
                    } else {
                        $body .= '$request->validated(), $id));';
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

                if ($this->option['service']) {
                    $body .= '$this->service->' . MethodsByClassEnum::DELETE . '($id));';
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
        $namespaceService = $this->getNamespaceServiceInterface() . DIRECTORY_SEPARATOR . $this->getClassName() . config('component.prefix.service') . config('component.prefix.interface');

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
        switch ($method) {
            case MethodsByClassEnum::STORE:
            case MethodsByClassEnum::UPDATE:
                $request = Str::ucfirst($method == MethodsByClassEnum::STORE ? MethodsByClassEnum::CREATE_METHOD : MethodsByClassEnum::UPDATE_METHOD);

                $namespaceRequest = $this->getNamespaceRequest() . DIRECTORY_SEPARATOR . $request . $this->getClassName() . config('component.prefix.request');

                $parameter = $methodClass
                    ->addParameter('request');

                if (!$this->option['request']) {
                    $methodClass
                        ->addComment('@param Request $request');

                    $namespace
                        ->addUse(Request::class);

                    $parameter
                        ->setType(Request::class);
                } else {
                    $methodClass
                        ->addComment('@param ' . $request . $this->getClassName() . config('component.prefix.request') . ' $request');

                    $namespace
                        ->addUse($namespaceRequest);

                    $parameter
                        ->setType($namespaceRequest);
                }

                if ($method === MethodsByClassEnum::UPDATE) {
                    $methodClass
                        ->addComment('@param ' . $this->primaryKey['type'] . ' $id')
                        ->addParameter('id')
                        ->setType($this->primaryKey['type']);
                }

                break;
            case MethodsByClassEnum::SHOW:
            case MethodsByClassEnum::DELETE:
                $methodClass
                    ->addComment('@param ' . $this->primaryKey['type'] . ' $id')
                    ->addParameter('id')
                    ->setType($this->primaryKey['type']);

                break;
        }
    }
}
