<?php

namespace Toshkq93\Components\Services;

use Doctrine\DBAL\Exception as DBALException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class ModelService
{
    /** @var string */
    private string $dateClass;

    /** @var array  */
    private array $properties = [];

    /**
     * @param Model $model
     * @return array
     */
    public function getProperties(Model $model): array
    {
        $hasDoctrine = interface_exists('Doctrine\DBAL\Driver');

        $this->dateClass = class_exists(Date::class)
            ? '\\' . get_class(Date::now())
            : '\Illuminate\Support\Carbon';

        if ($hasDoctrine) {
           $this->getPropertiesFromTable($model);
        }

        return $this->properties;
    }

    /**
     * @param Model $model
     * @return void
     * @throws DBALException
     */
    public function getPropertiesFromTable(Model $model): void
    {
        $table = $model->getConnection()->getTablePrefix() . $model->getTable();
        $schema = $model->getConnection()->getDoctrineSchemaManager();
        $databasePlatform = $schema->getDatabasePlatform();
        $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

        $platformName = $databasePlatform->getName();

        $database = null;
        if (strpos($table, '.')) {
            [$database, $table] = explode('.', $table);
        }

        $columns = $schema->listTableColumns($table, $database);

        if (!$columns) {
            return;
        }

        foreach ($columns as $column) {
            $name = $column->getName();
            if (in_array($name, $model->getDates())) {
                $type = $this->dateClass;
            } else {
                $type = $column->getType()->getName();
                switch ($type) {
                    case 'string':
                    case 'text':
                    case 'date':
                    case 'time':
                    case 'guid':
                    case 'datetimetz':
                    case 'datetime':
                    case 'decimal':
                    case 'float':
                        $type = 'string';
                        break;
                    case 'integer':
                    case 'bigint':
                    case 'smallint':
                        $type = 'int';
                        break;
                    case 'boolean':
                        switch ($platformName) {
                            case 'sqlite':
                            case 'mysql':
                                $type = 'int';
                                break;
                            default:
                                $type = 'boolean';
                                break;
                        }
                        break;
                    default:
                        $type = 'mixed';
                        break;
                }
            }

            if (!in_array($name, config('component.ignore_properties'))) {
                if (empty($this->properties[$name])) {
                    if ($type !== null) {
                        $newType = $type;
                        if (!$column->getNotnull()) {
                            $newType .= '|null';
                        }
                    } else {
                        $newType = 'mixed';
                    }

                    $this->properties[$name] = [
                        'type' => $newType,
                        'isPrimary' => $column->getAutoincrement()
                    ];
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getPrimaryKey(): array
    {
        $primaryKey = [];

        if (!empty($this->properties)){
            foreach ($this->properties as $key => $property){
                if ($property['isPrimary']){
                    $primaryKey = [
                        'name' => $key,
                        'type' => $property['type']
                    ];
                }
            }
        }

        return $primaryKey;
    }
}
