<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Scanner;

class MySQLScannerDriver extends AbstractScannerDriver
{    /**
     * Returns all table names in the current database.
     */
    public function getTables(): array
    {
        $rows = $this->connector->fetchQuery('SHOW TABLES');
        return array_map(fn($row) => reset($row), $rows);
    }
 
    /**
     * Returns columns normalized to flat shape:
     * [
     *   'id'    => ['nullable' => false, 'type' => 'int'],
     *   'email' => ['nullable' => false, 'type' => 'string'],
     * ]
     */
    public function getColumns(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT column_name, data_type, column_type, is_nullable
            FROM information_schema.columns
            WHERE table_name   = :table
            AND   table_schema = :schema
            ORDER BY ordinal_position
        ", ['table' => $table, 'schema' => $this->schemaName]);
 
        $result = [];
        foreach ($rows as $row) {
            $row  = array_change_key_case($row, CASE_LOWER);
            $name = $row['column_name'];
            $result[$name] = [
                'nullable' => $row['is_nullable'] === 'YES',
                'type' => $this->normalizeType($row['data_type'], $row['column_type']),
            ];
        }
        return $result;
    }
 
    /**
     * Returns foreign keys normalized to flat shape:
     * [
     *   'related_item_id' => [
     *     'nullable' => bool,
     *     'type'     => 'int',
     *     'fk'       => 'items',
     *     'onDelete' => 'CASCADE',   // null if no constraint exists
     *     'onUpdate' => 'RESTRICT',  // null if no constraint exists
     *   ],
     * ]
     */
    public function getForeignKeys(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT
                kcu.column_name,
                kcu.referenced_table_name,
                rc.delete_rule,
                rc.update_rule
            FROM information_schema.key_column_usage kcu
            JOIN information_schema.referential_constraints rc
                ON  rc.constraint_name   = kcu.constraint_name
                AND rc.constraint_schema = kcu.table_schema
            WHERE kcu.table_name            = :table
            AND   kcu.table_schema          = :schema
            AND   kcu.referenced_table_name IS NOT NULL
        ", ['table' => $table, 'schema' => $this->schemaName]);
 
        $result = [];
        foreach ($rows as $row) {
            $row     = array_change_key_case($row, CASE_LOWER);
            $colName = $row['column_name'];
            $result[$colName] = [
                'type' => 'int',
                'fk' => $row['referenced_table_name'],
                'onDelete' => $row['delete_rule'] ?? null,
                'onUpdate' => $row['update_rule'] ?? null,
            ];
        }
        return $result;
    }
 
    /**
     * Returns primary key column names for a given table.
     */
    public function getPrimaryKeys(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT column_name
            FROM information_schema.key_column_usage
            WHERE table_name      = :table
            AND   table_schema    = :schema
            AND   constraint_name = 'PRIMARY'
            ORDER BY ordinal_position
        ", ['table' => $table, 'schema' => $this->schemaName]);
 
        return array_map(function ($row) {
            $row = array_change_key_case($row, CASE_LOWER);
            return $row['column_name'];
        }, $rows);
    }
 
    /**
     * Returns indexes for a given table (excluding PRIMARY).
     */
    public function getIndexes(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT index_name, column_name, non_unique
            FROM information_schema.statistics
            WHERE table_name   = :table
            AND   table_schema = :schema
            AND   index_name  != 'PRIMARY'
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
 
    /**
     * Full scan: returns complete schema with flat normalized column shape.
     * FK columns override basic column entries, merging nullable from getColumns().
     */
    public function scan(): array
    {
        $schema = [];
        foreach ($this->getTables() as $table) {
            $columns = $this->getColumns($table);
            $foreignKeys = $this->getForeignKeys($table);
 
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
 
    protected function normalizeType(string $dataType, string $columnType = ''): string
    {
        if (strtolower($dataType) === 'tinyint' && str_contains($columnType, '(1)')) {
            return 'bool';
        }
        return match (strtolower($dataType)) {
            'int', 'integer', 'bigint', 'mediumint', 'smallint' => 'int',
            'varchar', 'char', 'text', 'mediumtext',
            'longtext', 'tinytext', 'enum', 'set' => 'string',
            'tinyint'  => 'bool',
            'float', 'double', 'decimal', 'numeric' => 'float',
            'datetime', 'timestamp' => 'datetime',
            'date' => 'date',
            'time' => 'time',
            'json' => 'json',
            default => $dataType,
        };
    }
}
