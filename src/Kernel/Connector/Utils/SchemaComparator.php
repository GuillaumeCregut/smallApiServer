<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils;

class SchemaComparator
{
    /**
     * Compare entity schema against DB schema.
     *
     * Normalized flat shape (both sides):
     * [
     *   'table_name' => [
     *     'columns' => [
     *       'id'              => ['nullable' => false, 'type' => 'int'],
     *       'related_item_id' => ['nullable' => false, 'type' => 'int', 'fk' => 'items', 'onDelete' => 'CASCADE', 'onUpdate' => 'RESTRICT'],
     *     ],
     *     'primary_keys' => ['id'],
     *     'indexes'      => [...],
     *   ]
     * ]
     *
     * Returns:
     * [
     *   'tables_to_create'   => ['table_name' => columns],
     *   'tables_to_drop'     => ['table_name'],
     *   'columns_to_add'     => ['table_name' => ['col' => colDef]],
     *   'columns_to_drop'    => ['table_name' => ['col']],
     *   'columns_to_alter'   => ['table_name' => ['col' => ['from' => [...], 'to' => [...]]]],
     *   'constraints_to_add' => ['table_name' => ['col' => ['fk' => '...', 'onDelete' => '...', 'onUpdate' => '...']]]
     * ]
     */
    public function compare(array $entitySchema, array $dbSchema): array
    {
        $diff = [
            'tables_to_create' => [],
            'tables_to_drop' => [],
            'columns_to_add' => [],
            'columns_to_drop' => [],
            'columns_to_alter' => [],
            'constraints_to_add' => [],
        ];

        $dbTables = array_keys($dbSchema);
        $entityTables = array_keys($entitySchema);

        // Tables in entity but missing in DB → create
        foreach (array_diff($entityTables, $dbTables) as $table) {
            $diff['tables_to_create'][$table] = $entitySchema[$table]['columns'];
        }

        // Tables in DB but missing in entity → drop
        foreach (array_diff($dbTables, $entityTables) as $table) {
            if('migrations' === $table) {
                continue;
            }
            $diff['tables_to_drop'][] = $table;
        }

        // Tables in both → compare columns and constraints
        foreach (array_intersect($entityTables, $dbTables) as $table) {
            $entityColumns = $entitySchema[$table]['columns'];
            $dbColumns = $dbSchema[$table]['columns'];

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
            if (!empty($columnDiff['constraints_to_add'])) {
                $diff['constraints_to_add'][$table] = $columnDiff['constraints_to_add'];
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
            && empty($diff['columns_to_alter'])
            && empty($diff['constraints_to_add']);
    }

    private function compareColumns(array $entityColumns, array $dbColumns): array
    {
        $result = [
            'to_add' => [],
            'to_drop' => [],
            'to_alter' => [],
            'constraints_to_add' => [],
        ];

        $entityColNames = array_keys($entityColumns);
        $dbColNames = array_keys($dbColumns);

        // Columns in entity but missing in DB → add
        foreach (array_diff($entityColNames, $dbColNames) as $col) {
            $result['to_add'][$col] = $entityColumns[$col];
        }

        // Columns in DB but missing in entity → drop
        foreach (array_diff($dbColNames, $entityColNames) as $col) {
            $result['to_drop'][] = $col;
        }

        // Columns in both → detect structural changes and missing constraints
        foreach (array_intersect($entityColNames, $dbColNames) as $col) {
            $changes = $this->detectColumnChanges($entityColumns[$col], $dbColumns[$col]);
            if (!empty($changes)) {
                $result['to_alter'][$col] = $changes;
            }

            // Check if FK constraint is missing in DB
            $constraint = $this->detectMissingConstraint($entityColumns[$col], $dbColumns[$col]);
            if ($constraint !== null) {
                $result['constraints_to_add'][$col] = $constraint;
            }
        }

        return $result;
    }

    /**
     * Detect structural changes (type, nullable only — fk/onDelete/onUpdate are not structural).
     */
    private function detectColumnChanges(array $entityCol, array $dbCol): array
    {
        $changes = [];

        if ($entityCol['type'] !== $dbCol['type']) {
            $changes['type'] = [
                'from' => $dbCol['type'],
                'to' => $entityCol['type'],
            ];
        }

        if ($entityCol['nullable'] !== $dbCol['nullable']) {
            $changes['nullable'] = [
                'from' => $dbCol['nullable'],
                'to' => $entityCol['nullable'],
            ];
        }

        return $changes;
    }

    /**
     * Detect if a FK constraint is defined in entity but missing or different in DB.
     * Returns constraint definition to add, or null if in sync.
     *
     * A constraint is missing if:
     * - Entity column has 'fk' key (it's a relation)
     * - DB column has no 'onDelete'/'onUpdate' (no constraint exists)
     */
    private function detectMissingConstraint(array $entityCol, array $dbCol): ?array
    {
        if (!isset($entityCol['fk'])) {
            return null;
        }

        // No constraint in DB at all
        if (!isset($dbCol['onDelete']) || $dbCol['onDelete'] === null) {
            return [
                'fk' => $entityCol['fk'],
                'onDelete' => $entityCol['onDelete'] ?? 'RESTRICT',
                'onUpdate' => $entityCol['onUpdate'] ?? 'RESTRICT',
            ];
        }

        return null;
    }
}
