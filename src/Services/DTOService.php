<?php

namespace Toshkq93\Components\Services;

use App\DTO\Input\Interfaces\CreateInputInterface;
use App\DTO\Input\Interfaces\UpdateInputInterface;
use App\DTO\Output\Interfaces\OutputInterface;
use File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Caster;
use Spatie\DataTransferObject\DataTransferObject;
use Toshkq93\Components\Enums\DTONameEnum;
use App\DTO\Casters\Date\CarbonCaster;
use Toshkq93\Components\Enums\MethodsByClassEnum;

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
        'Create{{name}}',
        'Update{{name}}',
    ];

    /** @var array|string[] */
    private array $macrossDTO = [
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
        if (!File::exists(
            app_path('DTO') . DIRECTORY_SEPARATOR . config('component.baseFile.dto') . '.php'
        )) {

            if (!File::exists(app_path('DTO'))) {
                File::makeDirectory(
                    app_path('DTO'),
                    0777,
                    true,
                    true
                );
            }

            $file = new PhpFile();

            $namespace = $file
                ->addNamespace(config('component.namespaces.base.dto'))
                ->addUse(DataTransferObject::class);

            $class = $namespace
                ->addClass(config('component.baseFile.dto'))
                ->setExtends(DataTransferObject::class);

            File::put(app_path('DTO') . DIRECTORY_SEPARATOR . config('component.baseFile.dto') . '.php', $file);
        }

        $this->createInterface();
    }

    private function createInterface()
    {
        if (!File::exists(app_path('DTO') . DIRECTORY_SEPARATOR . Str::ucfirst($this->folder) . DIRECTORY_SEPARATOR . 'Interfaces')) {
            File::makeDirectory(
                app_path('DTO') . DIRECTORY_SEPARATOR . Str::ucfirst($this->folder) . DIRECTORY_SEPARATOR . 'Interfaces',
                0777,
                true,
                true
            );

            if ($this->folder == DTONameEnum::INPUT){
                $namespaceInputDTO = config('component.namespaces.interface.dto.input') . $this->getFolderPath();

                foreach (MethodsByClassEnum::DTO_INPUT_NAMES as $dto) {
                    $file = new PhpFile();

                    $namespace = $file->addNamespace($namespaceInputDTO);

                    $nameInterface = Str::ucfirst($dto) . 'InputInterface';

                    $namespace
                        ->addInterface($nameInterface);

                    File::put(config('component.paths.rootPaths.dto.input') . DIRECTORY_SEPARATOR . $nameInterface . '.php', $file);
                }
            }else{
                $namespaceInputDTO = config('component.namespaces.interface.dto.output') . $this->getFolderPath();
                $nameInterface = 'OutputInterface';
                $file = new PhpFile();

                $namespace = $file
                    ->addNamespace($namespaceInputDTO);

                $namespace->addInterface($nameInterface);

                File::put(config('component.paths.rootPaths.dto.output') . DIRECTORY_SEPARATOR . $nameInterface . '.php', $file);
            }
        }
    }

    /**
     * @return void
     */
    public function create(): void
    {
        $this->createCasterDate();

        $namespaceFile = ($this->folder == DTONameEnum::OUTPUT ? config('component.namespaces.output') : config('component.namespaces.input')) . $this->getFolderPath();
        $namespaceBase = config('component.namespaces.base.dto') . DIRECTORY_SEPARATOR . config('component.baseFile.dto');

        if ($this->folder == DTONameEnum::INPUT) {
            unset($this->properties['id']);
            unset($this->properties['created_at']);
        }

        foreach ($this->replaceRealNames() as $dto) {
            $file = new PhpFile();

            $namespace = $file
                ->addNamespace($namespaceFile)
                ->addUse($namespaceBase);

            $class = $namespace
                ->addClass($dto)
                ->setFinal();

            $class->setExtends($namespaceBase);

            if ($this->folder == DTONameEnum::INPUT && Str::contains($dto, 'Create')) {
                $namespace
                    ->addUse(CreateInputInterface::class);

                $class
                    ->addImplement(CreateInputInterface::class);
            }elseif ($this->folder == DTONameEnum::INPUT && Str::contains($dto, 'Update')){
                $namespace
                    ->addUse(UpdateInputInterface::class);

                $class
                    ->addImplement(UpdateInputInterface::class);
            }else{
                $namespace
                    ->addUse(OutputInterface::class);

                $class
                    ->addImplement(OutputInterface::class);
            }

            if ($this->properties) {
                foreach ($this->properties as $property => $values) {

                    if (Str::contains($values['type'], Carbon::class)) {
                        $namespace->addUse(Carbon::class);
                    }

                    $property = Str::lcfirst(Str::studly($property));

                    $propertyNew = $class
                        ->addProperty($property)
                        ->setType($values['type'])
                        ->setPublic();

                    if (Str::contains($values['type'], Carbon::class)) {
                        $namespace->addUse(CastWith::class);
                        $namespace->addUse(CarbonCaster::class);
                        $propertyNew->addAttribute(CastWith::class, [new Literal('CarbonCaster::class')]);
                    }
                }

                $path = $this->folder == DTONameEnum::OUTPUT ? config('component.paths.output') : config('component.paths.input');

                File::makeDirectory($path . $this->getFolderPath(), 0777, true, true);

                File::put($path . $this->getFolderPath() . DIRECTORY_SEPARATOR . $dto . '.php', $file);
            }
        }
    }

    /**
     * @return void
     */
    private function createCasterDate(): void
    {
        $path = app_path('DTO') . '\Casters\Date';
        $namespace = 'App\\DTO\\Casters\\Date';

        if (!File::exists(app_path('DTO') . '\Casters')) {
            File::makeDirectory(
                $path, 0777,
                true,
                true
            );
        }

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($namespace)
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

        File::put($path . DIRECTORY_SEPARATOR . 'CarbonCaster.php', $file);
    }

    /**
     * @return array
     */
    private function replaceRealNames(): array
    {
        $namesFiles = [];

        if ($this->folder == DTONameEnum::INPUT) {
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
