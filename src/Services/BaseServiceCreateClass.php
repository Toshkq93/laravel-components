<?php

namespace Toshkq93\Components\Services;

use Illuminate\Support\Str;
use Toshkq93\Components\Enums\OperationSystemEnum;

class BaseServiceCreateClass
{
    protected string $argument;
    protected null|array $option;
    protected string $system;

    public function __construct()
    {
        $this->system = strtoupper(substr(PHP_OS, 0, 3));
    }

    public function setArgument(string $argument): void
    {
        $this->argument = $argument;
    }

    public function setOption(?array $option): void
    {
        $this->option = $option;
    }

    /** DTO */
    protected function getNamespaceBaseDto(string $path): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::before(Str::after($path, 'app' . DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR)
        );
    }

    protected function getNameBaseDto(): string
    {
        return config('component.base_name') . config('component.prefix.dto.base');
    }

    protected function getNamespaceDtoInputInterface(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.dto.input'), 'app' . DIRECTORY_SEPARATOR)
        );
    }

    protected function getNamespaceDtoOutputInterface(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.dto.output'), 'app' . DIRECTORY_SEPARATOR)
        );
    }

    protected function getNamespaceDtoOutput(string $path): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after($path, 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPathByDto()
        );
    }

    protected function getNamespaceDtoInput(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.input'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPathByDto()
        );
    }

    /** end DTO */

    /** Controller */

    protected function getNamespaceController(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.controller'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPath()
        );
    }

    protected function getNameController(): string
    {
        return $this->getClassName() . config('component.prefix.controller');
    }

    /** end Controller */

    /** Request */
    protected function getNamespaceRequest(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.request'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPathByDto()
        );
    }

    protected function getFolderPathByDto(): string
    {
        switch ($this->system){
            case OperationSystemEnum::WINDOWNS:
                return $this->argument;
            case OperationSystemEnum::LINUX:
                return $this->argument;
        }

        return Str::between($this->argument, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR) ?: $this->getClassName();
    }

    /** end Request */

    /** Resource */
    protected function getNamespaceResource(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.resource'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPathByDto()
        );
    }

    /** end Resource */

    /** Services */
    protected function getNamespaceServiceInterface(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.service'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPath()
        );
    }

    protected function getNamespaceBaseService(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.service'), 'app' . DIRECTORY_SEPARATOR)
        );
    }

    protected function getNameServiceInterface(): string
    {
        return $this->getClassName() . config('component.prefix.service') . config('component.prefix.interface');
    }

    protected function getNameBaseService(): string
    {
        return config('component.base_name') . config('component.prefix.service');
    }

    protected function getNameService(): string
    {
        return $this->getClassName() . config('component.prefix.service');
    }

    protected function getNamespaceService(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.service'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPath()
        );
    }

    /** end Services */

    /** Repository */
    protected function getNamespaceRepositoryInterface(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.repository'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPath()
        );
    }

    protected function getNamespaceBaseRepository(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.repository'), 'app' . DIRECTORY_SEPARATOR)
        );
    }

    protected function getNameRepositoryInterface(): string
    {
        return $this->getClassName() . config('component.prefix.repository') . config('component.prefix.interface');
    }

    protected function getNameBaseRepository(): string
    {
        return config('component.base_name') . config('component.prefix.repository');
    }

    protected function getNameRepository(): string
    {
        return $this->getClassName() . config('component.prefix.repository');
    }

    protected function getNamespaceRepository(): string
    {
        return $this->replaceNamespace(
            'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.repository'), 'app' . DIRECTORY_SEPARATOR) . $this->getFolderPath()
        );
    }

    /** end Repository */

    protected function replacePathBySystem(string $string)
    {
        switch ($this->system){
            case OperationSystemEnum::WINDOWNS:
                return Str::replace('/', '\\', $string);
            case OperationSystemEnum::LINUX:
                return Str::replace('\\', '/', $string);
        }

        return $string;
    }

    protected function getFolderPath(): string
    {
        return Str::beforeLast($this->argument, '\\');
    }

    protected function getClassName(): string
    {
        return class_basename($this->argument);
    }

    private function replaceNamespace(string $namespace)
    {
        return Str::replace('/', '\\', $namespace);
    }
}
