<?php

namespace App\Kernel\Connector\Utils\Scanner;

use App\Kernel\Connector\Interfaces\DatabaseScannerDriverInterface;

class PivotTableScanner
{
    public function __construct(private DatabaseScannerDriverInterface $driver)
    {
        
    }

    public function scan(array $expectedPivots): array
    {
        $result = [];
        foreach($expectedPivots as $pivot) {
            $table = $pivot['pivotTable'];
            $ownerCol = $pivot['ownerCol'];
            $targetCol = $pivot['targetCol'];

            if(!$this->driver->tableExists($table)) {
                $result[$table] = [
                    'exists' => false,
                    'columns_ok' => false,
                    'missing_columns' => [$ownerCol, $targetCol],
                    'extra_columns' => [],
                ];
                continue;
            }

            $dbColumns = array_keys($this->driver->getColumns($table));
            $expectedColumns = [$ownerCol, $targetCol];
            $missing = array_diff($expectedColumns, $dbColumns);
            $extra = array_diff($dbColumns, $expectedColumns);
            $result[$table] = [
                'exists' => true,
                'columns_ok' => empty($missing),
                'missing_columns' => array_values($missing),
                'extra_columns' => array_values($extra),
            ];
        }
        return $result;
    }

    public function getKnownPivotTableNames(array $expectedPivots): array
    {
        return array_column($expectedPivots, 'pivotTable');
    }
}