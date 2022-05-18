<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Caster;
use Spatie\DataTransferObject\Casters\ArrayCaster;
use Spatie\DataTransferObject\DataTransferObject;
use Toshkq93\Components\Enums\DTONameEnum;
use App\DTO\Casters\Date\CarbonCaster;

class DTOService
{
    /** @var string */
    private string $folder;

    /** @var array */
    private array $properties;

    /** @var string */
    private string $argument;

    /** @var array|string[] */
    private array $macrossFilters = [
        'Index{{name}}',
        'Create{{name}}',
        'Show{{name}}',
        'Update{{name}}',
        'Delete{{name}}'
    ];

    /** @var array|string[] */
    private array $macrossDTO = [
        '{{name}}Collection',
        '{{name}}',
    ];

    /**
     * @param string $folder
     */
    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
    }

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
    public function createBaseDTO(): void
    {
        if (!File::exists(config('path.paths.dto'))) {
            File::makeDirectory(
                config('path.paths.dto'), 0777,
                true,
                true
            );
        }

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace(config('path.namespaces.dto'))
            ->addUse(DataTransferObject::class);

        $class = $namespace
            ->addClass(config('path.baseFile.dto'))
            ->setExtends(DataTransferObject::class);

        File::put(config('path.paths.dto') . DIRECTORY_SEPARATOR . config('path.baseFile.dto') . '.php', $file);
    }

    public function create()
    {
        $this->createCasterDate();

        $namespaceFile = ($this->folder == DTONameEnum::DTO ? config('path.namespaces.dto') : config('path.namespaces.filter')) . $this->getFolderPath();

        foreach ($this->replaceRealNames() as $dto) {
            $file = new PhpFile();

            $namespace = $file
                ->addNamespace($namespaceFile)
                ->addUse(config('path.namespaces.base.dto'));

            $class = $namespace
                ->addClass($dto);

            $class->setExtends(config('path.namespaces.base.dto'));

            if ($this->properties) {
                if (Str::contains($dto, 'Collection')) {
                    $this->createCollection($namespaceFile, $dto);
                } else {
                    foreach ($this->properties as $property => $values) {
                        if (Str::contains($values['type'], Carbon::class)) {
                            $namespace->addUse(Carbon::class);
                        }

                        $property = Str::lcfirst(Str::studly($property));
                        $methodName = Str::studly($property);

                        if (Str::contains($dto, 'Delete') && Str::contains($dto, 'Show')) {
                            if ($values['isPrimary']) {
                                $this->createDeleteAndShowMethods($class, $property, $values, $methodName);
                            }
                        } else {
                            $propertyNew = $class
                                ->addProperty($property)
                                ->setType($values['type'])
                                ->setPublic();
                            if (Str::contains($values['type'], Carbon::class)) {
                                $namespace->addUse(CastWith::class);
                                $namespace->addUse(CarbonCaster::class);
                                $propertyNew->addAttribute(CastWith::class, [new Literal('CarbonCaster::class')]);
                            }
                            $methodGet = $class
                                ->addMethod("get{$methodName}")
                                ->setReturnType($values['type'])
                                ->setPublic();

                            $methodSet = $class
                                ->addMethod("set{$methodName}")
                                ->setReturnType('void')
                                ->setPublic();

                            $methodGet
                                ->addComment('@return ' . $values['type'])
                                ->setBody('return $this->' . $property . ';');

                            $methodSet
                                ->addComment('@param ' . $values['type'] . ' $' . $property)
                                ->setBody('$this->' . $property . ' = $' . $property . ';');
                            $methodSet->addParameter($property)->setType($values['type']);
                        }
                    }

                    $path = $this->folder == DTONameEnum::DTO ? config('path.paths.dto') : config('path.paths.filter');

                    File::makeDirectory($path . $this->getFolderPath(), 0777, true, true);

                    File::put($path . $this->getFolderPath() . DIRECTORY_SEPARATOR . $dto . '.php', $file);
                }
            }
        }
    }

    private function createCasterDate()
    {
        if (!File::exists(config('path.paths.dto') . '\Casters')) {
            File::makeDirectory(
                config('path.paths.dto') . '\Casters\Date', 0777,
                true,
                true
            );
        }

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace('App\\DTO\\Casters\\Date')
            ->addUse(Caster::class)
            ->addUse(Carbon::class);

        $class = $namespace
            ->addClass('CarbonCaster')
            ->addImplement(Caster::class);

        $method = $class
            ->addMethod('cast')
            ->setPublic();

        $method
            ->addComment('@param mixed $value')
            ->addComment('@return Carbon|null')
            ->setReturnType('mixed')
            ->addBody('return $value ? Carbon::parse($value) : null;');

        $method
            ->addParameter('value')
            ->setType('mixed');

        File::makeDirectory(config('path.paths.dto') . '\Casters\\Date', 0777, true, true);

        File::put(config('path.paths.dto') . '\Casters\\Date' . DIRECTORY_SEPARATOR . 'CarbonCaster.php', $file);
    }

    /**
     * @param ClassType $class
     * @param string $property
     * @param array $values
     * @param string $methodName
     * @return void
     */
    private function createDeleteAndShowMethods(
        ClassType $class,
        string    $property,
        array     $values,
        string    $methodName
    ): void
    {
        $class
            ->addProperty($property)
            ->setType($values['type'])
            ->setPublic();

        $methodGet = $class
            ->addMethod("get{$methodName}")
            ->setReturnType($values['type'])
            ->setPublic();

        $methodSet = $class
            ->addMethod("set{$methodName}")
            ->setReturnType('void')
            ->setPublic();

        $methodGet
            ->addComment('@return ' . $values['type'])
            ->setBody('return $this->' . $property . ';');

        $methodSet
            ->addComment('@param ' . $values['type'] . ' $' . $property)
            ->setBody('$this->' . $property . ' = $' . $property . ';');
        $methodSet->addParameter($property)->setType($values['type']);
    }

    /**
     * @param string $namespaceFile
     * @param string $dto
     * @return void
     */
    private function createCollection(string $namespaceFile, string $dto): void
    {
        $fileCollection = new PhpFile();
        $namespace = $fileCollection
            ->addNamespace($namespaceFile)
            ->addUse(CastWith::class)
            ->addUse(ArrayCaster::class)
            ->addUse(config('path.namespaces.base.dto'));

        $classCollection = $namespace
            ->addClass($dto);

        $classCollection->setExtends(config('path.namespaces.base.dto'));

        $prop = $classCollection
            ->addProperty('items')
            ->setType('array')
            ->setPublic();
        $prop->addAttribute(CastWith::class, [new Literal('ArrayCaster::class'), 'itemType' => new Literal($this->className() . "::class")]);

        $methodGet = $classCollection
            ->addMethod("get" . $prop->getName())
            ->setReturnType($prop->getType())
            ->setPublic();

        $methodGet
            ->addComment('@return ' . $prop->getType())
            ->setBody('return $this->' . $prop->getName() . ';');

        File::makeDirectory(config('path.paths.dto') . $this->getFolderPath(), 0777, true, true);

        File::put(config('path.paths.dto') . $this->getFolderPath() . DIRECTORY_SEPARATOR . $dto . '.php', $fileCollection);
    }

    /**
     * @return array
     */
    private function replaceRealNames(): array
    {
        $namesFiles = [];

        if ($this->folder == DTONameEnum::FILTER) {
            foreach ($this->macrossFilters as $macros) {
                $namesFiles[] = Str::replace(
                    '{{name}}',
                    $this->className(),
                    $macros
                );
            }
        } else {
            foreach ($this->macrossDTO as $macros) {
                $namesFiles[] = Str::replace(
                    '{{name}}',
                    $this->className(),
                    $macros
                );
            }
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
