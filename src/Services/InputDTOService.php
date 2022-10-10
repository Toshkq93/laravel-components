<?php

namespace Toshkq93\Components\Services;

use File;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Spatie\DataTransferObject\Attributes\CastWith;
use App\DTO\Casters\Date\CarbonCaster;
use Toshkq93\Components\Enums\MethodsByClassEnum;

final class InputDTOService extends BaseServiceCreateClass
{
    private string $folder;
    private array $properties;
    private string $pathInputDTO;
    private string $pathInputDTOInterface;

    public function __construct()
    {
        parent::__construct();
        $this->pathInputDTO = $this->replacePathBySystem(config('component.paths.input'));
        $this->pathInputDTOInterface = $this->replacePathBySystem(config('component.paths.interface.dto.input'));
    }

    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function createInterfaces(): void
    {
        $path = Str::before(Str::after($this->pathInputDTO, 'app' . DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
        $dirNameByInterface = Str::afterLast($this->pathInputDTOInterface, DIRECTORY_SEPARATOR);

        if (!File::exists(app_path($path) . DIRECTORY_SEPARATOR . Str::ucfirst($this->folder) . DIRECTORY_SEPARATOR . $dirNameByInterface)) {
            File::makeDirectory(
                app_path($path) . DIRECTORY_SEPARATOR . Str::ucfirst($this->folder) . DIRECTORY_SEPARATOR . $dirNameByInterface,
                0777,
                true,
                true
            );

            $namespaceInputDTO = $this->getNamespaceDtoInputInterface();

            foreach (MethodsByClassEnum::DTO_INPUT_NAMES as $dto) {
                $file = new PhpFile();

                $namespace = $file
                    ->addNamespace($namespaceInputDTO)
                    ->addUse(Arrayable::class);

                $nameInterface = $dto . config('component.prefix.dto.input') . config('component.prefix.interface');

                $namespace
                    ->addInterface($nameInterface)
                    ->setExtends(Arrayable::class);

                File::put(config('component.paths.interface.dto.input') . DIRECTORY_SEPARATOR . $nameInterface . '.php', $file);
            }
        }
    }

    public function create(): void
    {
        File::makeDirectory(
            config('component.paths.input') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto(),
            0777,
            true,
            true
        );

        unset($this->properties['id']);
        unset($this->properties['created_at']);

        foreach (MethodsByClassEnum::DTO_INPUT_NAMES as $dto) {
            $nameDTO = $dto . $this->getClassName() . config('component.prefix.dto.input');
            $namespaceInterface = $this->getNamespaceDtoInputInterface() . '\\' . $dto . config('component.prefix.dto.input') . config('component.prefix.interface');
            $namespaceBaseDto = $this->getNamespaceBaseDto($this->pathInputDTO) . '\\' . $this->getNameBaseDto();

            $file = new PhpFile();

            $namespace = $file
                ->addNamespace($this->getNamespaceDtoInput())
                ->addUse($namespaceInterface)
                ->addUse($namespaceBaseDto);

            $class = $namespace
                ->addClass($nameDTO)
                ->addImplement($namespaceInterface)
                ->setExtends($namespaceBaseDto)
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

            File::put($this->pathInputDTO . DIRECTORY_SEPARATOR . $this->getFolderPathByDto() . DIRECTORY_SEPARATOR . $nameDTO . '.php', $file);
        }
    }
}
