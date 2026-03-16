<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Scanner;

use Exception;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

class PostGreScannerDriver extends AbstractScannerDriver
{
    /**
     * Returns all table names in the public schema.
     */
    public function getTables(): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT tablename 
            FROM pg_tables 
            WHERE schemaname = :schema
            ORDER BY tablename
        ", ['schema' => $this->schemaName]);
 
        return array_map(fn($row) => $row['tablename'], $rows);
    }
 
    /**
     * Returns columns normalized to match EntityAnalyzer output shape.
     */
    public function getColumns(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT column_name, data_type, character_maximum_length,
                   numeric_precision, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name   = :table
            AND   table_schema = :schema
            ORDER BY ordinal_position
        ", ['table' => $table, 'schema' => $this->schemaName]);
 
        $result = [];
        foreach ($rows as $row) {
            $name = $row['column_name'];
            $result[$name] = [
                'nullable' => $row['is_nullable'] === 'YES',
                'type'     => $this->normalizeType($row['data_type'], $row['column_default'] ?? ''),
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
            SELECT kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON  kcu.constraint_name = tc.constraint_name
                AND kcu.table_schema    = tc.table_schema
                AND kcu.table_name      = tc.table_name
            WHERE tc.constraint_type = 'PRIMARY KEY'
            AND   tc.table_name      = :table
            AND   tc.table_schema    = :schema
            ORDER BY kcu.ordinal_position
        ", ['table' => $table, 'schema' => $this->schemaName]);
 
        return array_map(fn($row) => $row['column_name'], $rows);
    }
 
    /**
     * Returns foreign keys normalized to match EntityAnalyzer relation shape.
     */
    public function getForeignKeys(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT
                kcu.column_name,
                ccu.table_name          AS referenced_table_name,
                rc.delete_rule,
                rc.update_rule
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON  kcu.constraint_name = tc.constraint_name
                AND kcu.table_schema    = tc.table_schema
            JOIN information_schema.constraint_column_usage ccu
                ON  ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema    = tc.table_schema
            JOIN information_schema.referential_constraints rc
                ON  rc.constraint_name        = tc.constraint_name
                AND rc.constraint_schema      = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
            AND   tc.table_name      = :table
            AND   tc.table_schema    = :schema
        ", ['table' => $table, 'schema' => $this->schemaName]);
 
        $result = [];
        foreach ($rows as $row) {
            $colName = $row['column_name'];
            $result[$colName] = [
                'type'     => 'int',
                'fk'       => $row['referenced_table_name'],
                'onDelete' => $row['delete_rule'] ?? null,
                'onUpdate' => $row['update_rule'] ?? null,
            ];
        }
        return $result;
    }
 
    /**
     * Returns indexes for a given table (excluding PRIMARY KEY).
     */
    public function getIndexes(string $table): array
    {
        $rows = $this->connector->fetchQuery("
            SELECT
                i.relname                                    AS index_name,
                a.attname                                    AS column_name,
                ix.indisunique                               AS is_unique
            FROM pg_class t
            JOIN pg_index ix     ON ix.indrelid  = t.oid
            JOIN pg_class i      ON i.oid         = ix.indexrelid
            JOIN pg_attribute a  ON a.attrelid    = t.oid
                                AND a.attnum       = ANY(ix.indkey)
            JOIN pg_namespace n  ON n.oid          = t.relnamespace
            WHERE t.relname  = :table
            AND   n.nspname  = :schema
            AND   ix.indisprimary = false
            ORDER BY i.relname
        ", ['table' => $table, 'schema' => $this->schemaName]);
 
        $result = [];
        foreach ($rows as $row) {
            $indexName = $row['index_name'];
            if (!isset($result[$indexName])) {
                $result[$indexName] = [
                    'unique'  => $row['is_unique'] === 't',
                    'columns' => [],
                ];
            }
            $result[$indexName]['columns'][] = $row['column_name'];
        }
        return $result;
    }
 
    /**
     * Full scan: returns the complete schema for all tables.
     * FK columns override their basic column entry to carry relation metadata.
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
 
    /**
     * Maps PostgreSQL native types to generic types matching EntityAnalyzer output.
     *
     * PostgreSQL SERIAL/BIGSERIAL are syntactic sugar — they appear as
     * integer/bigint in information_schema with a nextval() default.
     * We detect auto-increment via the column_default value.
     */
    private function normalizeType(string $dataType, string $columnDefault = ''): string
    {
        // Detect SERIAL (auto-increment) → int
        if (str_starts_with($columnDefault, 'nextval(')) {
            return 'int';
        }
 
        return match (strtolower($dataType)) {
            'integer', 'bigint', 'smallint', 'int', 'int2', 'int4', 'int8' => 'int',
            'character varying', 'varchar', 'char',
            'character', 'text', 'citext' => 'string',
            'boolean', 'bool'  => 'bool',
            'numeric', 'decimal', 'real',
            'double precision', 'float4', 'float8' => 'float',
            'timestamp', 'timestamp without time zone',
            'timestamp with time zone', 'timestamptz' => 'datetime',
            'date' => 'date',
            'time', 'time without time zone' => 'time',
            'json', 'jsonb'  => 'json',
            default  => $dataType,
        };
    }
}
