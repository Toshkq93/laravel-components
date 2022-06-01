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
        $fullPathFolder = config('component.paths.request') . $this->getFolderPath();

        if (!File::exists($fullPathFolder)) {
            File::makeDirectory(
                $fullPathFolder, 0777,
                true,
                true
            );
        }

        $this->generatePHP($this->replaceRealNames(), $fullPathFolder);
    }

    /**
     * @param array $requests
     * @param string $path
     * @return void
     */
    public function generatePHP(array $requests, string $path): void
    {

        $namespaceClass = config('component.namespaces.request') . $this->getFolderPath();

        foreach ($requests as $request) {
            $filePath = $path . DIRECTORY_SEPARATOR . $request . 'Request.php';
            $className = $request . 'Request';

            $file = new PhpFile();

            $namespace = $file
                ->addNamespace($namespaceClass)
                ->addUse(FormRequest::class);

            $class = $namespace
                ->addClass($className)
                ->setFinal()
                ->setExtends(FormRequest::class);

            $this->createMethods($class);

            File::put($filePath, $file);
        }
    }

    private function createMethods(
        ClassType $class,
    )
    {
        $class
            ->addMethod('authorize')
            ->setReturnType('bool')
            ->addComment('@return bool')
            ->addBody('return true;');

        $methodRules = $class
            ->addMethod('rules')
            ->setReturnType('array')
            ->addComment('@return array');

        if ($this->properties) {
            $this->generateMethodWithProperties($methodRules);
        } else {
            $methodRules
                ->addBody('return [];');
        }
    }

    /**
     * @param Method $methodRules
     * @return void
     */
    private function generateMethodWithProperties(
        Method $methodRules
    ): void
    {
        $methodRules
            ->addBody('return [');

        unset($this->properties['id']);
        unset($this->properties['created_at']);

        foreach ($this->properties as $key => $property) {

            $collectionTypes = Str::of($property['type'])->explode('|');
            $methodRules->addBody("\t'{$key}' => [");

            if ($collectionTypes->count() > 1) {
                foreach ($collectionTypes as $type) {
                    if (Str::contains($type, 'Carbon')) {
                        $methodRules
                            ->addBody("\t\t'date',");
                    } elseif (Str::contains($type, 'null')) {
                        $methodRules
                            ->addBody("\t\t'nullable',");
                    } else {
                        $methodRules
                            ->addBody("\t\t'{$type}',");
                    }
                }
            } else {
                $methodRules
                    ->addBody("\t\t'{$collectionTypes->first()}',")
                    ->addBody("\t\t'required',");
            }

            $methodRules->addBody("\t],");
        }

        $methodRules
            ->addBody('];');
    }

    /**
     * @return array
     */
    private function replaceRealNames(): array
    {
        $namesFiles = [];

        foreach (MethodsByClassEnum::REQUEST_NAMES as $request) {
            $namesFiles[] = Str::ucfirst($request) . $this->className();
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
