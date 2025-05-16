<?php

declare(strict_types=1);

namespace Composite\LaravelAutoCrud\Builders;

use Composite\LaravelAutoCrud\Services\ModelService;
use Composite\LaravelAutoCrud\Services\TableColumnsService;
use Composite\LaravelAutoCrud\Traits\TableColumnsTrait;
use Illuminate\Support\Str;
use Composite\LaravelAutoCrud\Services\HelperService;

class VueBuilder extends BaseBuilder
{
    use TableColumnsTrait;

    public function __construct()
    {
        parent::__construct();
        $this->tableColumnsService = new TableColumnsService;
        $this->modelService = new ModelService;
    }
    public function createIndex(array $modelData, string $request, bool $overwrite = false): string
    {
        $basePath = 'resources/js/pages/'.HelperService::toSnakeCase(Str::plural($modelData['modelName']));
        return $this->fileService->createFromStub($modelData, 'index.vue', $basePath, '', $overwrite, function ($modelData) use ($request) {
            $model = $this->getFullModelNamespace($modelData);
            $requestName = explode('\\', $request);

            return [
                '{{ requestNamespace }}' => $request,
                '{{ modelNamespace }}' => $model,
                '{{ request }}' => end($requestName),
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
                '{{ viewPath }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ modelPlural }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ modelPluralCapitalized }}' => Str::plural($modelData['modelName']),
                '{{ routeName }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ data }}' => HelperService::formatArrayToJsSyntax($this->getInterfaceData($modelData), false, 4)
            ];
        }, false, 'vue', 'Index');
    }

    private function getInterfaceData(array $modelData): array
    {
        $columns = $this->getAvailableColumns($modelData);

        $validationRules = [];

        foreach ($columns as $column) {
            $rules = [];
            $columnType = $column['type'];
            $maxLength = $column['max_length'];
            $isUnique = $column['is_unique'];
            $allowedValues = $column['allowed_values'];
            // Handle column types
            switch ($columnType) {
                case 'string':
                case 'char':
                case 'varchar':
                    $rules[] = 'string';
                    if ($maxLength) {
                        $rules[] = 'max:'.$maxLength;
                    }
                    break;

                case 'integer':
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $rules[] = 'integer';
                    if (str_contains($columnType, 'unsigned')) {
                        $rules[] = 'min:0';
                    }
                    break;

                case 'boolean':
                    $rules[] = 'boolean';
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    $rules[] = 'string';
                    break;

                case 'text':
                case 'longtext':
                case 'mediumtext':
                    $rules[] = 'string';
                    break;

                case 'decimal':
                case 'float':
                case 'double':
                    $rules[] = 'numeric';
                    break;

                case 'enum':
                    if (! empty($allowedValues)) {
                        $rules[] = 'in:'.implode(',', $allowedValues);
                    }
                    break;

                case 'json':
                    $rules[] = 'json';
                    break;

                case 'binary':
                case 'blob':
                    $rules[] = 'string'; // Handle binary data as string for simplicity
                    break;

                default:
                    $rules[] = 'string'; // Default fallback
                    break;
            }

            $columnName = $column['name'];

            // Handle unique columns
            if ($isUnique) {
                $rules[] = 'unique:'.$column['table'].','.$columnName;
            }

            // Add rules to the validation array
            $validationRules[$columnName] = implode('|', $rules);
        }

        return $validationRules;
    }
}
