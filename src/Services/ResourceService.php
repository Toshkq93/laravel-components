<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpFile;

class ResourceService
{
    /** @var string */
    private string $argument;

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
    public function create(): void
    {
        File::makeDirectory(
            config('component.paths.resource') . $this->getFolderPath(), 0777,
            true,
            true
        );

        $namespaceClass = config('component.namespaces.resource') . $this->getFolderPath();

        $this->createResource($namespaceClass);
        $this->createCollection($namespaceClass);
    }

    /**
     * @param string $namespaceClass
     * @return void
     */
    private function createCollection(string $namespaceClass): void
    {
        $file = new PhpFile();
        $nameClass = $this->className() . 'Collection';
        $namespace = $file
            ->addNamespace($namespaceClass)
            ->addUse(ResourceCollection::class);

        $class = $namespace
            ->addClass($nameClass)
            ->setFinal()
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
            config('component.paths.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameClass . '.php',
            $file
        );
    }

    /**
     * @param string $namespaceClass
     * @return void
     */
    private function createResource(string $namespaceClass): void
    {
        $nameClass = $this->className() . 'Resource';

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($namespaceClass)
            ->addUse(JsonResource::class);

        $class = $namespace
            ->addClass($nameClass)
            ->setFinal()
            ->setExtends(JsonResource::class);

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
            config('component.paths.resource') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameClass . '.php',
            $file
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
    private function className(): string
    {
        return class_basename($this->argument);
    }
}
