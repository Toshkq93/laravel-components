<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Toshkq93\Components\Enums\MethodsByClassEnum;

class RequestService
{
    /** @var array */
    private array $properties;

    /** @var string */
    private string $argument;

    /** @var array|string[] */
    private array $fileNames = [
        'Index{{name}}',
        'Create{{name}}',
        'Show{{name}}',
        'Update{{name}}',
        'Delete{{name}}'
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
    public function create(): void
    {
        $fullPathFolder = config('path.paths.request') . $this->getFolderPath();

        if (!File::exists($fullPathFolder)) {
            File::makeDirectory(
                $fullPathFolder, 0777,
                true,
                true
            );
        }

        $this->generatePHP($this->replaceRealNames(), $fullPathFolder);
    }


    public function generatePHP(array $requests, string $path): void
    {
        $namespaceClass = config('path.namespaces.request') . $this->getFolderPath();

        foreach ($requests as $request) {
            $filePath = $path . DIRECTORY_SEPARATOR . $request . 'Request.php';
            $className = $request . 'Request';

            $file = new PhpFile();

            $namespace = $file
                ->addNamespace($namespaceClass)
                ->addUse(FormRequest::class);

            $class = $namespace
                ->addClass($className)
                ->setExtends(FormRequest::class);

            $this->createMethods($namespace, $class, $request);

            File::put($filePath, $file);
        }
    }

    private function createMethods(
        PhpNamespace $namespace,
        ClassType    $class,
        string       $className
    )
    {
        $methodAuthorize = $class
            ->addMethod('authorize')
            ->setReturnType('bool')
            ->addComment('@return bool')
            ->addBody('return true;');

        $methodRules = $class
            ->addMethod('rules')
            ->setReturnType('array')
            ->addComment('@return array');

        if ($this->properties) {
            $this->generateMethodsWithProperties($class, $namespace, $methodRules, $className);
        } else {
            $methodRules
                ->addBody('return [];');
        }
    }

    private function generateMethodsWithProperties(
        ClassType    $class,
        PhpNamespace $namespace,
        Method       $methodRules,
        string       $className
    )
    {
        $nameFilter = $className . 'Filter';
        $namespaceFilter = config('path.namespaces.filter') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $nameFilter;
        $className = Str::lcfirst($className);

        $namespace
            ->addUse($namespaceFilter);

        $this->createMethodGetFilterDTO($class, $namespaceFilter, $nameFilter, $className, $methodRules);
    }

    private function createMethodGetFilterDTO(
        ClassType $class,
        string    $namespaceFilter,
        string    $nameFilter,
        string    $className,
        Method    $methodRules
    )
    {
        $methodGetDTO = $class
            ->addMethod('getFilterDTO')
            ->setReturnType($namespaceFilter)
            ->addComment('@return ' . $nameFilter);

        if (
            Str::contains($className, MethodsByClassEnum::INDEX) or
            Str::contains($className, MethodsByClassEnum::CREATE) or
            Str::contains($className, MethodsByClassEnum::UPDATE)
        ) {
            $methodRules
                ->addBody('return [');

            foreach ($this->properties as $key => $property) {
                $collectionTypes = Str::of($property['type'])->explode('|');
                $methodRules->addBody("'{$key}' => [");

                if ($collectionTypes->count() > 1){
                    foreach ($collectionTypes as $type){
                        if (Str::contains($type,'Carbon')){
                            $methodRules
                                ->addBody("'date',");
                        }else{
                            $methodRules
                                ->addBody("'{$type}',");
                        }
                    }
                }else{
                    $methodRules
                        ->addBody("'{$collectionTypes->first()}'");
                }

                $methodRules->addBody("],");
            }

            $methodRules
                ->addBody('];');
            $methodGetDTO
                ->addBody('return new ' . $nameFilter . '($this->validated());');
        } elseif (
            Str::contains($className, MethodsByClassEnum::SHOW) or
            Str::contains($className, MethodsByClassEnum::DELETE)
        ) {
            $methodRules
                ->addBody('return [];');
            $methodGetDTO
                ->addBody('return new ' . $nameFilter . '(id: request()->id);');
        }
    }

    /**
     * @return array
     */
    private function replaceRealNames(): array
    {
        $namesFiles = [];

        foreach ($this->fileNames as $request) {
            $namesFiles[] = Str::replace(
                '{{name}}',
                $this->className(),
                $request
            );
        }

        return $namesFiles;
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
