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

    /** Controller */

    protected function getNamespaceController(): string
    {
        return 'App\Http\Controllers' . $this->getFolderPath();
    }

    protected function getNameController(): string
    {
        return $this->getClassName() . config('component.prefix.controller');
    }

    /** end Controller */

    /** Request */

    protected function getNamespaceRequest(): string
    {
        return 'App\Http\Requests' . DIRECTORY_SEPARATOR . $this->getFolderPathByDto();
    }

    /** end Request */

    /** Resource */

    protected function getNamespaceResource(): string
    {
        return 'App\Http\Resources' . DIRECTORY_SEPARATOR . $this->getFolderPathByDto();
    }

    /** end Resource */
    /** DTO */

    protected function getNamespaceBaseDto(): string
    {
        return 'App\DTO' . DIRECTORY_SEPARATOR . config('component.base_name') . config('component.prefix.dto.base');
    }

    protected function getNameBaseDto(): string
    {
        return config('component.base_name') . config('component.prefix.dto.base');
    }

    protected function getNamespaceDtoInputInterface(): string
    {
        return 'App\DTO\Input\Interfaces';
    }

    protected function getNamespaceDtoOutputInterface(): string
    {
        return 'App\DTO\Output\Interfaces';
    }

    protected function getNamespaceDtoOutput(): string
    {
        return 'App\DTO\Output\\' . $this->getFolderPathByDto();
    }

    protected function getNamespaceDtoInput(): string
    {
        return 'App\DTO\Input\\' . $this->getFolderPathByDto();
    }

    /** end DTO */

    /** Services */

    /**
     * @return string
     */
    protected function getNamespaceServiceInterface(): string
    {
        return 'App\Services\Interfaces' . $this->getFolderPath();
    }

    /**
     * @return string
     */
    protected function getNamespaceBaseService(): string
    {
        return 'App\Services';
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
        return 'App\Services' . $this->getFolderPath();
    }

    /** end Services */

    /** Repository */

    /**
     * @return string
     */
    protected function getNamespaceRepositoryInterface(): string
    {
        return 'App\Repositories\Interfaces' . $this->getFolderPath();
    }

    /**
     * @return string
     */
    protected function getNamespaceBaseRepository(): string
    {
        return 'App\Repositories';
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
        return 'App\Repositories' . $this->getFolderPath();
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
