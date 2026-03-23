<?php

namespace App\Kernel\Connector\Utils;

use App\Kernel\Connector\Interfaces\DatabaseScannerDriverInterface;

class PivotSchemaComparator
{
    public function __construct(private DatabaseScannerDriverInterface $driver) {}

    /**
     * Undocumented function
     *
     * @param array $expectedPivots : result from EntityManyToManyTransformer::transform()
     * @param array $scanResult : result from PivotTableScanner::scan()
     * @param array $allDbTables : result from MySQLScannerDriver::getTables BEFORE filtering
     * @return array 
     * [
     *   'pivot_tables_to_create' => [
     *     ['pivotTable' => '...', 'ownerCol' => '...', 'targetCol' => '...',
     *      'ownerTable' => '...', 'targetTable' => '...']
     *   ],
     *   'pivot_tables_to_drop'   => ['table_name', ...],
     *   'pivot_tables_to_fix'    => [
     *     'table_name' => ['missing_columns' => [...], 'extra_columns' => [...]]
     *   ],
     * ]
     */
    public function compare(
        array $expectedPivots,
        array $scanResult,
        array $allDbTables
    ): array {
        $diff = [
            'pivot_tables_to_create' => [],
            'pivot_tables_to_drop' => [],
            'pivot_tables_to_fix' => [],
        ];

        $expectedNames = array_column($expectedPivots, 'pivotTable');
        foreach ($expectedPivots as $pivot) {
            $table = $pivot['pivotTable'];
            $status = $scanResult[$table] ?? null;
            if (null === $status || !$status['exists']) {
                $diff['pivot_tables_to_create'][] = $pivot;
                continue;
            }

            if (!$status['columns_ok']) {
                $diff['pivot_tables_to_fix'][$table] = [
                    'missing_columns' => $status['missing_columns'],
                    'extra_columns' => $status['extra_columns']
                ];
            }
        }

        foreach ($allDbTables as $dbTable) {
            if (in_array($dbTable, $expectedNames)) {
                continue;
            }
            if ('migrations' === $dbTable) {
                continue;
            }
            if ($this->looksLikePivotTable($dbTable)) {
                $diff['pivot_tables_to_drop'][] = $dbTable;
            }
        }
        return $diff;
    }

    public function isInSync(array $diff): bool
    {
        return empty($diff['pivot_tables_to_create'])
            && empty($diff['pivot_tables_to_drop'])
            && empty($diff['pivot_tables_to_fix']);
    }

    private function looksLikePivotTable(string $table): bool
    {
        $columns = $this->driver->getColumns($table);
        if(count($columns) !== 2) {
            return false;
        }
        $fkColumns = $this->driver->getForeignKeys($table);
        return count($fkColumns) === 2;
    }
}
