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
use App\DTO\Casters\Date\CarbonCaster;

class DTOService extends BaseServiceCreateClass
{
    /** @var string */
    private string $folder;

    /** @var array */
    private array $properties;

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
     * @return void
     */
    public function createBaseDTO(): void
    {
        $path = Str::before(Str::after(config('component.paths.input'), 'app\\'), '\\');
        $dirNameByInterface = Str::afterLast(config('component.paths.interface.dto.output'), '\\');

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
                ->addNamespace($this->getNamespaceBaseDto())
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

    /**
     * @return void
     */
    private function createInterface(): void
    {
        $namespaceInputDTO = $this->getNamespaceDtoOutputInterface();
        $nameInterface = config('component.prefix.dto.output') . config('component.prefix.interface');
        $file = new PhpFile();

        $namespace = $file
            ->addNamespace($namespaceInputDTO);

        $namespace->addInterface($nameInterface);

        File::put(config('component.paths.interface.dto.output') . DIRECTORY_SEPARATOR . $nameInterface . '.php', $file);
    }

    /**
     * @return void
     */
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
        $namespaceInterface = $this->getNamespaceDtoOutputInterface() . DIRECTORY_SEPARATOR . config('component.prefix.dto.output') . config('component.prefix.interface');
        $namespaceBaseDto = $this->getNamespaceBaseDto() . DIRECTORY_SEPARATOR . $this->getNameBaseDto();

        $namespace = $file
            ->addNamespace($this->getNamespaceDtoOutput())
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

    /**
     * @return void
     */
    private function createCasterDate(): void
    {
        $pathToDto = Str::before(Str::after(config('component.paths.input'), 'app\\'), '\\');
        $path = app_path($pathToDto) . '\Casters\Date';
        $namespace = 'App\\' . $pathToDto . '\Casters\Date';

        if (!File::exists(app_path($pathToDto) . '\Casters')) {
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
}
