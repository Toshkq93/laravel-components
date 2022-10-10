<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Toshkq93\Components\Enums\MethodsByClassEnum;

final class RequestService extends BaseServiceCreateClass
{
    /** @var array */
    private array $properties;

    /**
     * @param array $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * @return void
     */
    public function create(): void
    {
        $fullPathFolder = config('component.paths.request') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto();

        if (!File::exists($fullPathFolder)) {
            File::makeDirectory(
                $fullPathFolder,
                0777,
                true,
                true
            );
        }

        $this->generatePHP();
    }

    /**
     * @return void
     */
    public function generatePHP(): void
    {
        foreach (MethodsByClassEnum::REQUEST_NAMES as $request) {
            $requestName = $request . $this->getClassName() . config('component.prefix.request');
            $filePath = config('component.paths.request') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto() . DIRECTORY_SEPARATOR . $requestName . '.php';

            $file = new PhpFile();

            $namespace = $file
                ->addNamespace($this->getNamespaceRequest())
                ->addUse(FormRequest::class);

            $class = $namespace
                ->addClass($requestName)
                ->setFinal()
                ->setExtends(FormRequest::class);

            $this->createMethods($class);

            File::put($filePath, $file);
        }
    }

    /**
     * @param ClassType $class
     * @return void
     */
    private function createMethods(ClassType $class): void
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
    private function generateMethodWithProperties(Method $methodRules): void
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
}
