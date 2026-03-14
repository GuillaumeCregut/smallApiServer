<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Scanner;

use Exception;

class SQLiteScannerDriver extends AbstractScannerDriver
{
   
    public function getColumns(string $table): array
    {
        throw new Exception('Not implemented');
    }
    public function getTables(): array
    {
       throw new Exception('Not implemented');
    }

    
    public function getForeignKeys(string $table): array
    {
        throw new Exception('Not implemented');
    }

    public function getPrimaryKeys(string $table): array
    {
        throw new Exception('Not implemented');
    }

    public function getIndexes(string $table): array
    {
        throw new Exception('Not implemented');
    }

    public function scan(): array
    {
        throw new Exception('Not implemented');
    }

    protected function normalizeColumn(array $row): array
    {
        throw new Exception('Not implemented');
    }

     protected function getSchemaName(): string
    {
        throw new Exception('Not implemented');
    }
}
    //Start of class
    // public function __construct(private ConnectorInterface $connector) {}
 
    // /**
    //  * Returns all user table names (excludes SQLite internal tables).
    //  */
    // public function getTables(): array
    // {
    //     $rows = $this->connector->fetchQuery("
    //         SELECT name 
    //         FROM sqlite_master 
    //         WHERE type = 'table' 
    //         AND   name NOT LIKE 'sqlite_%'
    //         ORDER BY name
    //     ");
 
    //     return array_map(fn($row) => $row['name'], $rows);
    // }
 
    // /**
    //  * Returns columns normalized to match EntityAnalyzer output shape.
    //  * Uses PRAGMA table_info() — SQLite has no information_schema.
    //  *
    //  * PRAGMA table_info() returns:
    //  * cid | name | type | notnull | dflt_value | pk
    //  */
    // public function getColumns(string $table): array
    // {
    //     $rows = $this->connector->fetchQuery("PRAGMA table_info(`{$table}`)");
 
    //     $result = [];
    //     foreach ($rows as $row) {
    //         $result[$row['name']] = [
    //             'nullable' => $row['notnull'] == 0,
    //             'type'     => $this->normalizeType($row['type']),
    //             'relation' => [],
    //         ];
    //     }
    //     return $result;
    // }
 
    // /**
    //  * Returns primary key column names.
    //  * PRAGMA table_info() marks pk > 0 for primary key columns.
    //  */
    // public function getPrimaryKeys(string $table): array
    // {
    //     $rows = $this->connector->fetchQuery("PRAGMA table_info(`{$table}`)");
 
    //     $pks = array_filter($rows, fn($row) => $row['pk'] > 0);
 
    //     // Sort by pk value to respect composite PK ordering
    //     usort($pks, fn($a, $b) => $a['pk'] <=> $b['pk']);
 
    //     return array_map(fn($row) => $row['name'], $pks);
    // }
 
    // /**
    //  * Returns foreign keys normalized to match EntityAnalyzer relation shape.
    //  * Uses PRAGMA foreign_key_list().
    //  *
    //  * PRAGMA foreign_key_list() returns:
    //  * id | seq | table | from | to | on_update | on_delete | match
    //  */
    // public function getForeignKeys(string $table): array
    // {
    //     $rows = $this->connector->fetchQuery("PRAGMA foreign_key_list(`{$table}`)");
 
    //     // Retrieve nullability info from table_info for FK columns
    //     $columnInfo = $this->getColumnsRaw($table);
 
    //     $result = [];
    //     foreach ($rows as $row) {
    //         $colName  = $row['from'];
    //         $nullable = isset($columnInfo[$colName]) ? $columnInfo[$colName]['notnull'] == 0 : true;
 
    //         $result[$colName] = [
    //             'nullable' => $nullable,
    //             'type'     => 'relation',
    //             'relation' => [
    //                 'entity' => $row['table'],
    //                 'key'    => $colName,
    //             ],
    //         ];
    //     }
    //     return $result;
    // }
 
    // /**
    //  * Returns indexes for a given table (excluding PRIMARY KEY).
    //  * Uses PRAGMA index_list() and PRAGMA index_info().
    //  */
    // public function getIndexes(string $table): array
    // {
    //     $indexes = $this->connector->fetchQuery("PRAGMA index_list(`{$table}`)");
 
    //     $result = [];
    //     foreach ($indexes as $index) {
    //         // SQLite marks auto-created PK indexes with origin = 'pk'
    //         if ($index['origin'] === 'pk') {
    //             continue;
    //         }
 
    //         $indexName = $index['name'];
    //         $columns   = $this->connector->fetchQuery("PRAGMA index_info(`{$indexName}`)");
 
    //         $result[$indexName] = [
    //             'unique'  => $index['unique'] == 1,
    //             'columns' => array_map(fn($col) => $col['name'], $columns),
    //         ];
    //     }
    //     return $result;
    // }
 
    // /**
    //  * Full scan: returns the complete schema for all tables.
    //  * FK columns override their basic column entry to carry relation metadata.
    //  */
    // public function scan(): array
    // {
    //     $schema = [];
    //     foreach ($this->getTables() as $table) {
    //         $columns     = $this->getColumns($table);
    //         $foreignKeys = $this->getForeignKeys($table);
 
    //         // FK columns override basic column entries (type becomes 'relation')
    //         foreach ($foreignKeys as $colName => $fkInfo) {
    //             $columns[$colName] = $fkInfo;
    //         }
 
    //         $schema[$table] = [
    //             'columns'      => $columns,
    //             'primary_keys' => $this->getPrimaryKeys($table),
    //             'indexes'      => $this->getIndexes($table),
    //         ];
    //     }
    //     return $schema;
    // }
 
    // /**
    //  * Raw PRAGMA table_info() rows keyed by column name.
    //  * Used internally to get nullability for FK columns.
    //  */
    // private function getColumnsRaw(string $table): array
    // {
    //     $rows   = $this->connector->fetchQuery("PRAGMA table_info(`{$table}`)");
    //     $result = [];
    //     foreach ($rows as $row) {
    //         $result[$row['name']] = $row;
    //     }
    //     return $result;
    // }
 
    // /**
    //  * Maps SQLite type affinities to generic types matching EntityAnalyzer output.
    //  *
    //  * SQLite is loosely typed — column types are free-form strings.
    //  * We normalize by checking for keywords in the declared type.
    //  */
    // private function normalizeType(string $declaredType): string
    // {
    //     $type = strtolower(trim($declaredType));
 
    //     // Integer affinity
    //     if (str_contains($type, 'int')) {
    //         return 'int';
    //     }
 
    //     // Boolean (SQLite has no native bool — stored as int)
    //     if ($type === 'boolean' || $type === 'bool') {
    //         return 'bool';
    //     }
 
    //     // Text affinity
    //     if (
    //         str_contains($type, 'char') ||
    //         str_contains($type, 'text') ||
    //         str_contains($type, 'clob') ||
    //         str_contains($type, 'varchar') ||
    //         str_contains($type, 'string')
    //     ) {
    //         return 'string';
    //     }
 
    //     // Float affinity
    //     if (
    //         str_contains($type, 'real') ||
    //         str_contains($type, 'float') ||
    //         str_contains($type, 'double') ||
    //         str_contains($type, 'decimal') ||
    //         str_contains($type, 'numeric')
    //     ) {
    //         return 'float';
    //     }
 
    //     // Datetime
    //     if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
    //         return 'datetime';
    //     }
    //     if ($type === 'date') {
    //         return 'date';
    //     }
    //     if ($type === 'time') {
    //         return 'time';
    //     }
 
    //     // JSON (not native in SQLite but sometimes declared)
    //     if ($type === 'json') {
    //         return 'json';
    //     }
 
    //     // SQLite NUMERIC affinity fallback
    //     return 'string';
    // }