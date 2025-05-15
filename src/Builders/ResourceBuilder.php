<?php

declare(strict_types=1);

namespace Composite\LaravelAutoCrud\Builders;

use Composite\LaravelAutoCrud\Services\HelperService;
use Composite\LaravelAutoCrud\Services\ModelService;
use Composite\LaravelAutoCrud\Services\TableColumnsService;
use Composite\LaravelAutoCrud\Traits\TableColumnsTrait;

class ResourceBuilder extends BaseBuilder
{
    use TableColumnsTrait;

    public function __construct()
    {
        parent::__construct();
        $this->modelService = new ModelService;
        $this->tableColumnsService = new TableColumnsService;
    }

    public function create(array $modelData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'resource', 'Http/Resources', 'Resource', $overwrite, function ($modelData) {
            return ['{{ data }}' => HelperService::formatArrayToPhpSyntax($this->getResourcesData($modelData), true)];
        });
    }

    private function getResourcesData(array $modelData): array
    {
        $columns = $this->getAvailableColumns($modelData);

        $data = [];

        foreach ($columns as $column) {
            $columnName = $column['name'];
            $data[$columnName] = '$this->'.$columnName;
        }

        return $data;
    }
}
