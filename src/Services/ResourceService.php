<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpFile;

class ResourceService extends BaseServiceCreateClass
{
    /**
     * @return void
     */
    public function create(): void
    {
        File::makeDirectory(
            config('component.paths.resource') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto(), 0777,
            true,
            true
        );

        $this->createResource();
        $this->createCollection();
    }

    /**
     * @return void
     */
    private function createCollection(): void
    {
        $nameClass = $this->getClassName() . config('component.prefix.resource.collection');

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->getNamespaceResource())
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
            config('component.paths.resource') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto() . DIRECTORY_SEPARATOR . $nameClass . '.php',
            $file
        );
    }

    /**
     * @return void
     */
    private function createResource(): void
    {
        $nameClass = $this->getClassName() . config('component.prefix.resource.resource');

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($this->getNamespaceResource())
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
            config('component.paths.resource') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto() . DIRECTORY_SEPARATOR . $nameClass . '.php',
            $file
        );
    }
}
