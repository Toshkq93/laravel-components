<?php

namespace Toshkq93\Components\Services;

use Illuminate\Support\Str;

class BaseServiceCreateClass
{
    /** @var string */
    protected string $argument;

    /** @var array|null */
    protected null|array $option;

    /**
     * @param string $argument
     */
    public function setArgument(string $argument): void
    {
        $this->argument = $argument;
    }

    /**
     * @param array|null $option
     */
    public function setOption(?array $option): void
    {
        $this->option = $option;
    }

    /** DTO */

    protected function getNamespaceBaseDto(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::before(Str::after(config('component.paths.input'), 'app\\'), '\\');
    }

    protected function getNameBaseDto(): string
    {
        return config('component.base_name') . config('component.prefix.dto.base');
    }

    protected function getNamespaceDtoInputInterface(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.dto.input'), 'app\\');
    }

    protected function getNamespaceDtoOutputInterface(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.dto.output'), 'app\\');
    }

    protected function getNamespaceDtoOutput(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.output'), 'app\\') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto();
    }

    protected function getNamespaceDtoInput(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.input'), 'app\\') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto();
    }

    /** end DTO */

    /** Controller */

    protected function getNamespaceController(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.controller'), 'app\\') . $this->getFolderPath();
    }

    protected function getNameController(): string
    {
        return $this->getClassName() . config('component.prefix.controller');
    }

    /** end Controller */

    /** Request */

    protected function getNamespaceRequest(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.request'), 'app\\') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto();
    }

    /** end Request */

    /** Resource */

    protected function getNamespaceResource(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.resource'), 'app\\') . DIRECTORY_SEPARATOR . $this->getFolderPathByDto();
    }

    /** end Resource */

    /** Services */

    /**
     * @return string
     */
    protected function getNamespaceServiceInterface(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.service'), 'app\\') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    protected function getNamespaceBaseService(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.service'), 'app\\');
    }

    /**
     * @return string
     */
    protected function getNameServiceInterface(): string
    {
        return $this->getClassName() . config('component.prefix.service') . config('component.prefix.interface');
    }

    /**
     * @return string
     */
    protected function getNameBaseService(): string
    {
        return config('component.base_name') . config('component.prefix.service');
    }

    /**
     * @return string
     */
    protected function getNameService(): string
    {
        return $this->getClassName() . config('component.prefix.service');
    }

    /**
     * @return string
     */
    protected function getNamespaceService(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.service'), 'app\\') . $this->getFolderPath();
    }

    /** end Services */

    /** Repository */

    /**
     * @return string
     */
    protected function getNamespaceRepositoryInterface(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.interface.repository'), 'app\\') . $this->getFolderPath();
    }

    /**
     * @return string
     */
    protected function getNamespaceBaseRepository(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.repository'), 'app\\');
    }

    /**
     * @return string
     */
    protected function getNameRepositoryInterface(): string
    {
        return $this->getClassName() . config('component.prefix.repository') . config('component.prefix.interface');
    }

    /**
     * @return string
     */
    protected function getNameBaseRepository(): string
    {
        return config('component.base_name') . config('component.prefix.repository');
    }

    /**
     * @return string
     */
    protected function getNameRepository(): string
    {
        return $this->getClassName() . config('component.prefix.repository');
    }

    /**
     * @return string
     */
    protected function getNamespaceRepository(): string
    {
        return 'App' . DIRECTORY_SEPARATOR . Str::after(config('component.paths.repository'), 'app\\') . $this->getFolderPath();
    }

    /** end Repository */

    /**
     * @return string
     */
    protected function getFolderPath(): string
    {
        return Str::beforeLast($this->argument, '\\');
    }

    protected function getFolderPathByDto(): string
    {
        return Str::between($this->argument, '\\', '\\') ?: $this->getClassName();
    }

    /**
     * @return string
     */
    protected function getClassName(): string
    {
        return class_basename($this->argument);
    }
}
