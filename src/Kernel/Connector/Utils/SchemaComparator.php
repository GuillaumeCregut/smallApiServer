<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils;

class SchemaComparator
{
    /**
     * Compare entity schema (source of truth) against DB schema.
     ** $entitySchema shape (after converter, snake_case):
     * [
     *   'users' => [
     *     'id'    => ['nullable' => false, 'type' => 'int',    'relation' => []],
     *     'email' => ['nullable' => false, 'type' => 'string', 'relation' => []],
     *   ]
     * ]
     *
     * $dbSchema shape (from DatabaseScanner::scan()):
     * [
     *   'users' => [
     *     'columns'      => [ 'id' => [...], 'email' => [...] ],
     *     'primary_keys' => ['id'],
     *     'indexes'      => [...],
     *   ]
     * ]
     *
     * Returns a diff array:
     * [
     *   'tables_to_create' => ['table_name' => columns],
     *   'tables_to_drop'   => ['table_name'],
     *   'columns_to_add'   => ['table_name' => ['col' => columnDef]],
     *   'columns_to_drop'  => ['table_name' => ['col']],
     *   'columns_to_alter' => ['table_name' => ['col' => ['from' => [...], 'to' => [...]]]],
     * ]
     *
     * @param array $entitySchema
     * @param array $dbSchema
     * @return array
     */
    public function compare(array $entitySchema, array $dbSchema): array
    {
        $diff = [
            'tables_to_create' => [],
            'tables_to_drop'   => [],
            'columns_to_add'   => [],
            'columns_to_drop'  => [],
            'columns_to_alter' => [],
        ];

        $dbTables     = array_keys($dbSchema);
        $entityTables = array_keys($entitySchema);

        // Tables present in entities but missing in DB → need to be created
        foreach (array_diff($entityTables, $dbTables) as $table) {
            $diff['tables_to_create'][$table] = $entitySchema[$table];
        }

        // Tables present in DB but missing in entities → candidates for drop
        foreach (array_diff($dbTables, $entityTables) as $table) {
            $diff['tables_to_drop'][] = $table;
        }

        // Tables present in both → compare columns
        foreach (array_intersect($entityTables, $dbTables) as $table) {
            $entityColumns = $entitySchema[$table];
            $dbColumns     = $dbSchema[$table]['columns'];
 
            $columnDiff = $this->compareColumns($entityColumns, $dbColumns);
 
            if (!empty($columnDiff['to_add'])) {
                $diff['columns_to_add'][$table] = $columnDiff['to_add'];
            }
            if (!empty($columnDiff['to_drop'])) {
                $diff['columns_to_drop'][$table] = $columnDiff['to_drop'];
            }
            if (!empty($columnDiff['to_alter'])) {
                $diff['columns_to_alter'][$table] = $columnDiff['to_alter'];
            }
        }
        return $diff;
    }

    public function isInSync(array $diff): bool
    {
        return empty($diff['tables_to_create'])
            && empty($diff['tables_to_drop'])
            && empty($diff['columns_to_add'])
            && empty($diff['columns_to_drop'])
            && empty($diff['columns_to_alter']);
    }

    /**
     * Compare columns of a single table.
     */
    private function compareColumns(array $entityColumns, array $dbColumns): array
    {
        $result = [
            'to_add'   => [],
            'to_drop'  => [],
            'to_alter' => [],
        ];
 
        $entityColNames = array_keys($entityColumns);
        $dbColNames     = array_keys($dbColumns);
 
        // Columns in entity but missing in DB → add
        foreach (array_diff($entityColNames, $dbColNames) as $col) {
            $result['to_add'][$col] = $entityColumns[$col];
        }
 
        // Columns in DB but missing in entity → drop
        foreach (array_diff($dbColNames, $entityColNames) as $col) {
            $result['to_drop'][] = $col;
        }
 
        // Columns in both → check for differences
        foreach (array_intersect($entityColNames, $dbColNames) as $col) {
            $changes = $this->detectColumnChanges(
                $entityColumns[$col],
                $dbColumns[$col]
            );
            if (!empty($changes)) {
                $result['to_alter'][$col] = $changes;
            }
        }
 
        return $result;
    }

    /**
     * Detect what changed between entity column definition and DB column definition.
     * Returns array of changes, empty if identical.
     */
    private function detectColumnChanges(array $entityCol, array $dbCol): array
    {
        $changes = [];
 
        // Type changed
        if ($entityCol['type'] !== $dbCol['type']) {
            $changes['type'] = [
                'from' => $dbCol['type'],
                'to'   => $entityCol['type'],
            ];
        }
 
        // Nullability changed
        if ($entityCol['nullable'] !== $dbCol['nullable']) {
            $changes['nullable'] = [
                'from' => $dbCol['nullable'],
                'to'   => $entityCol['nullable'],
            ];
        }
 
        // Relation changed
        if ($entityCol['relation'] !== $dbCol['relation']) {
            $changes['relation'] = [
                'from' => $dbCol['relation'],
                'to'   => $entityCol['relation'],
            ];
        }
 
        return $changes;
    }
}