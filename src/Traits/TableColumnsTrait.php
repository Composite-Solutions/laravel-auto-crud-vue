<?php

namespace Composite\LaravelAutoCrud\Traits;

use Composite\LaravelAutoCrud\Services\ModelService;
use Composite\LaravelAutoCrud\Services\TableColumnsService;

trait TableColumnsTrait
{
    protected TableColumnsService $tableColumnsService;

    protected ModelService $modelService;

    public function getAvailableColumns(array $modelData): array
    {
        $table = $this->modelService->getFullModelNamespace($modelData);

        return $this->tableColumnsService->getAvailableColumns($table);
    }
}
