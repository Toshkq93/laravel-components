<?php

namespace Toshkq93\Components\Services;

use Doctrine\DBAL\Exception as DBALException;
use Illuminate\Support\Facades\Date;

class ModelService
{
    /** @var string */
    private string $dateClass;
    /** @var string */
    private string $namespaceModel;
    /** @var array */
    private array $properties = [];
    /** @var array|string[] */
    private array $ignoredPropeties = [
        'updated_at',
        'deleted_at'
    ];

    /**
     * @return array
     * @throws DBALException
     */
    public function getProperties($model): array
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
     * @param $model
     * @return void
     */
    public function getPropertiesFromTable($model): void
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
                    case 'float':
                        $type = 'float';
                        break;
                    default:
                        $type = 'mixed';
                        break;
                }
            }

            if (!$column->getNotnull()) {
                $this->nullableColumns[$name] = true;
            }

            if (!in_array($name, $this->ignoredPropeties)) {
                $this->setProperty(
                    $name,
                    $type,
                    $column->getAutoincrement(),
                    !$column->getNotnull()
                );
            }
        }
    }

    /**
     * @param string $name
     * @param string|null $type
     * @param bool $isPrimary
     * @param bool $nullable
     * @return void
     */
    private function setProperty(string $name, string $type = null, bool $isPrimary = false, bool $nullable = false): void
    {
        if (!isset($this->properties[$name])) {
            $this->properties[$name] = [];
            $this->properties[$name]['type'] = 'mixed';
            $this->properties[$name]['isPrimary'] = false;
        }
        if ($type !== null) {
            $newType = $type;
            if ($nullable) {
                $newType .= '|null';
            }
            $this->properties[$name]['type'] = $newType;
        }
        $this->properties[$name]['isPrimary'] = $isPrimary;
    }
}
