<?php

namespace Toshkq93\Components\Services;

use App\DTO\Input\Interfaces\CreateInputInterface;
use App\DTO\Input\Interfaces\UpdateInputInterface;
use App\DTO\Output\Interfaces\OutputInterface;
use File;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Caster;
use Spatie\DataTransferObject\DataTransferObject;
use App\DTO\Casters\Date\CarbonCaster;

final class DTOService extends BaseServiceCreateClass
{
    private string $folder;
    private array $properties;
    private string $pathInputDTO;
    private string $pathOutputDTO;
    private string $pathOutputInterface;

    public function __construct()
    {
        parent::__construct();
        $this->pathInputDTO = $this->replacePathBySystem(config('component.paths.input'));
        $this->pathOutputInterface = $this->replacePathBySystem(config('component.paths.interface.dto.output'));
        $this->pathOutputDTO = $this->replacePathBySystem(config('component.paths.output'));
    }

    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function createBaseDTO(): void
    {
        $path = Str::before(Str::after($this->pathInputDTO, 'app' . DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
        $dirNameByInterface = Str::afterLast($this->pathOutputInterface, DIRECTORY_SEPARATOR);

        if (!File::exists(
            app_path($path) . DIRECTORY_SEPARATOR . $this->getNameBaseDto() . '.php'
        )) {
            File::makeDirectory(
                app_path($path),
                0777,
                true,
                true
            );

            $file = new PhpFile();

            $namespace = $file
                ->addNamespace($this->getNamespaceBaseDto($this->pathInputDTO))
                ->addUse(DataTransferObject::class);

            $namespace
                ->addClass($this->getNameBaseDto())
                ->setExtends(DataTransferObject::class);

            File::put(app_path($path) . DIRECTORY_SEPARATOR . $this->getNameBaseDto() . '.php', $file);
        }

        if (!File::exists(app_path($path) . DIRECTORY_SEPARATOR . Str::ucfirst($this->folder) . DIRECTORY_SEPARATOR . $dirNameByInterface)) {
            File::makeDirectory(
                app_path($path) . DIRECTORY_SEPARATOR . Str::ucfirst($this->folder) . DIRECTORY_SEPARATOR . $dirNameByInterface,
                0777,
                true,
                true
            );

            $this->createInterface();
        }
    }

    public function create(): void
    {
        $this->createCasterDate();

        File::makeDirectory(
            config('component.paths.output') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto(),
            0777,
            true,
            true
        );

        $file = new PhpFile();

        $nameDTO = $this->getClassName() . config('component.prefix.dto.output');
        $namespaceInterface = $this->getNamespaceDtoOutputInterface() . '\\' . config('component.prefix.dto.output') . config('component.prefix.interface');
        $namespaceBaseDto = $this->getNamespaceBaseDto($this->pathInputDTO) . '\\' . $this->getNameBaseDto();

        $namespace = $file
            ->addNamespace($this->getNamespaceDtoOutput($this->pathOutputDTO))
            ->addUse($namespaceInterface)
            ->addUse($namespaceBaseDto);

        $class = $namespace
            ->addClass($nameDTO)
            ->setExtends($namespaceBaseDto)
            ->addImplement($namespaceInterface)
            ->setFinal();

        foreach ($this->properties as $property => $values) {
            if (Str::contains($values['type'], Carbon::class)) {
                $namespace->addUse(Carbon::class);
            }

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

        File::put(config('component.paths.output') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto() . DIRECTORY_SEPARATOR . $nameDTO . '.php', $file);
    }

    private function createCasterDate(): void
    {
        $pathToDto = Str::before(Str::after($this->pathOutputDTO, 'app' . DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
        $path = app_path($pathToDto) . DIRECTORY_SEPARATOR . 'Casters' . DIRECTORY_SEPARATOR . 'Date';
        $namespace = 'App\\' . $pathToDto . '\Casters\Date';

        if (!File::exists($path)) {
            File::makeDirectory(
                $path,
                0777,
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
            ->setFinal()
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

    private function createInterface(): void
    {
        $namespaceInputDTO = $this->getNamespaceDtoOutputInterface();
        $nameInterface = config('component.prefix.dto.output') . config('component.prefix.interface');

        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($namespaceInputDTO)
            ->addUse(Arrayable::class);

        $interface = $namespace->addInterface($nameInterface);

        $interface->addExtend(Arrayable::class);

        File::put(config('component.paths.interface.dto.output') . DIRECTORY_SEPARATOR . $nameInterface . '.php', $file);
    }
}
