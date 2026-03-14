<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Scanner;


use PDO;

class MySQLScannerDriver extends AbstractScannerDriver
{

    public function getTables(): array
    {
        $rows = $this->connector->fetchQuery('SHOW TABLES');
        return array_map(fn($row) => reset($row), $rows);
    }

    public function getColumns(string $table): array
    {
        $rows = $this->connector->fetchQuery("SELECT column_name, data_type, column_type,
                   is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name = :table AND table_schema = :schema
            ORDER BY ordinal_position", ['table' => $table, 'schema' => $this->schemaName]);
        $result = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $name = $row['column_name'];
            $result[$name] = [
                'nullable' => $row['is_nullable'] === 'YES',
                'type' => $this->normalizeType(
                    $row['data_type'],
                    $row['column_type']
                ),
                'relation' => []
            ];
        }
        return $result;
    }


    /**
     * Returns foreign keys for a given table, normalized to match EntityAnalyzer relation shape:
     * [
     *   'author_id' => [
     *     'nullable' => bool,
     *     'type'     => 'relation',
     *     'relation' => [
     *       'entity' => 'authors',    // referenced table
     *       'key'    => 'author_id'   // local FK column
     *     ]
     *   ]
     * ]
     */
    public function getForeignKeys(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT
                kcu.column_name,
                kcu.referenced_table_name,
                kcu.referenced_column_name
            FROM information_schema.key_column_usage kcu
            WHERE kcu.table_name            = :table
            AND   kcu.table_schema          = :schema
            AND   kcu.referenced_table_name IS NOT NULL
        ", ['table' => $table, 'schema' => $this->schemaName]);

        $result = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $colName = $row['column_name'];
            $result[$colName] = [
                'type'     => 'relation',
                'relation' => [
                    'entity' => $row['referenced_table_name'],
                    'key'    => $colName,
                ],
            ];
        }
        return $result;
    }

    public function getPrimaryKeys(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT column_name
            FROM information_schema.key_column_usage
            WHERE table_name = :table
            AND table_schema = :schema
            AND constraint_name = 'PRIMARY'
            ORDER BY ordinal_position
        ", ['table' => $table, 'schema' => $this->schemaName]);
        return array_map(function ($row) {
            $row = array_change_key_case($row, CASE_LOWER);
            return $row['column_name'];
        }, $rows);
    }

    public function getIndexes(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT index_name, column_name, non_unique
            FROM information_schema.statistics
            WHERE table_name = :table
            AND table_schema = :schema
            AND index_name != 'PRIMARY'
            ORDER BY index_name, seq_in_index
        ", ['table' => $table, 'schema' => $this->schemaName]);

        $result = [];
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $indexName = $row['index_name'];
            if (!isset($result[$indexName])) {
                $result[$indexName] = [
                    'unique' => $row['non_unique'] == 0,
                    'columns' => [],
                ];
            }
            $result[$indexName]['columns'][] = $row['column_name'];
        }
        return $result;
    }

    public function scan(): array
    {
        $schema = [];
        foreach ($this->getTables() as $table) {
            $columns = $this->getColumns($table);
            $foreignKeys = $this->getForeignKeys($table);

            // FK columns override basic column entries (type becomes 'relation')
            foreach ($foreignKeys as $colName => $fkInfo) {
                $columns[$colName] = array_merge($fkInfo, [
                    'nullable' => $columns[$colName]['nullable'] ?? false,
                ]);
            }

            $schema[$table] = [
                'columns' => $columns,
                'primary_keys' => $this->getPrimaryKeys($table),
                'indexes' => $this->getIndexes($table),
            ];
        }
        return $schema;
    }

    protected function getSchemaName(): string
    {
        $query = 'select DATABASE() as db';
        return $this->connector->FetchQueryOnce($query)['db'];
    }

    protected function normalizeColumn(array $row): array
    {
        return [
            'name' => $row['column_name'] ?? $row['COLUMN_NAME'],
            'type' => $this->normalizeType($row['data_type'] ?? $row['DATA_TYPE']),
            'length' => $row['character_maximum_length'] ?? null,
            'nullable' => ($row['is_nullable'] ?? $row['IS_NULLABLE']) === 'YES',
            'default' => $row['column_default'] ?? $row['COLUMN_DEFAULT'],
        ];
    }

    private function normalizeType(string $dataType, string $columnType = ''): string
    {
        if (strtolower($dataType) === 'tinyint' && str_contains($columnType, '(1)')) {
            return 'bool';
        }
        return match (strtolower($dataType)) {
            'int', 'integer', 'bigint', 'smallint' => 'int',
            'varchar', 'char', 'text', 'longtext' => 'string',
            'tinyint' => 'bool',
            'datetime', 'timestamp' => 'datetime',
            'float', 'double', 'decimal' => 'float',
            default => $dataType
        };
    }

    private function getTablesInformations(PDO $pdo, array $tables): array
    {
        $tableArray = [];
        foreach ($tables as $table) {
            $query = "SELECT * FROM information_schema.columns WHERE table_name = '{$table}'";
            $st = $pdo->query($query);
            $results = $st->fetchAll();
            $cols = [];
            foreach ($results as $row) {
                $fieldName = $row['COLUMN_NAME'];
                $col = self::getFieldInfo($row);
                $cols[$fieldName] = $col;
            }
            $tableArray[$table] = $cols;
        }
        return $tableArray;
    }

    private static function getFieldInfo(array $field): array
    {
        $col = [];
        $col['type'] = $field['COLUMN_TYPE'];
        $col['nullable'] = $field['IS_NULLABLE'];
        return $col;
    }
}
