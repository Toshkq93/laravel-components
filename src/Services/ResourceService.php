<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpFile;

class ResourceService
{
    /** @var array */
    private array $properties;

    /** @var string */
    private string $argument;

    /** @var array|string[] */
    private array $methods = [
        'index',
        'create',
        'show'
    ];

    /**
     * @param array $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * @param string $argument
     */
    public function setArgument(string $argument): void
    {
        $this->argument = $argument;
    }

    /**
     * @return void
     */
    public function createResources(): void
    {
        File::makeDirectory(
            config('component.paths.resource') . $this->getFolderPath(), 0777,
            true,
            true
        );

        $namespaceClass = config('component.namespaces.resource') . $this->getFolderPath();

        foreach ($this->methods as $method) {
            $nameClass = Str::ucfirst($method . $this->className() . 'Resource');

            $file = $this->generatePHP($nameClass, $namespaceClass);

            File::put(
                config('component.paths.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameClass . '.php',
                $file
            );
        }
        $this->createCollection($namespaceClass);
    }

    /**
     * @param string $namespaceClass
     * @return void
     */
    private function createCollection(string $namespaceClass): void
    {
        $file = new PhpFile();
        $nameClass = 'Index' . $this->className() . 'Collection';
        $namespace = $file
            ->addNamespace($namespaceClass)
            ->addUse(ResourceCollection::class);

        $class = $namespace
            ->addClass($nameClass)
            ->setExtends(ResourceCollection::class);

        $methodClass = $class
            ->addMethod('toArray');

        $methodClass
            ->addComment('Transform the resource into an array.')
            ->addComment('')
            ->addComment('@param  \Illuminate\Http\Request  $request')
            ->addComment('@return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable')
            ->addParameter('request');

        $methodClass
            ->addBody('return parent::toArray($request);');

        File::put(
            config('path.paths.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameClass . '.php',
            $file
        );
    }

    /**
     * @param string $method
     * @param string $namespaceClass
     * @return PhpFile
     */
    private function generatePHP(string $nameClass, string $namespaceClass): PhpFile
    {
        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($namespaceClass)
            ->addUse(JsonResource::class);

        $class = $namespace
            ->addClass($nameClass)
            ->setExtends(JsonResource::class);

        $methodClass = $class
            ->addMethod('toArray');

        $methodClass
            ->addComment('Transform the resource into an array.')
            ->addComment('')
            ->addComment('@param  \Illuminate\Http\Request  $request')
            ->addComment('@return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable')
            ->addParameter('request');


        if ($this->properties) {
            $namespace
                ->addUse(config('component.namespaces.output') . $this->argument . 'Output');

            $methodClass
                ->addBody('/** @var ' . $this->className() . 'Output $this */')
                ->addBody('return [');
            foreach ($this->properties as $key => $property) {
                $value = Str::studly($key);

                $methodClass
                    ->addBody("'{$key}' => " . '$this->get' . $value . '(),');
            }
            $methodClass
                ->addBody('];');
        } else {
            $methodClass
                ->addBody('return parent::toArray($request);');
        }

        return $file;
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
