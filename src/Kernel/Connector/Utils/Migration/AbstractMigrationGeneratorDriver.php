<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

use App\Kernel\Connector\Interfaces\MigrationGeneratorInterface;


abstract class AbstractMigrationGeneratorDriver implements MigrationGeneratorInterface
{
    /**
     * Wrap an identifier (table or column name) with the correct quoting style.
     * MySQL uses backticks, PostgreSQL and SQLite use double quotes.
     */
    abstract protected function wrapIdentifier(string $name): string;

    /**
     * Map a generic type (from EntityAnalyzer) to a native SQL type string.
     */
    abstract protected function toSQLType(string $colName, string $genericType): string;

    /**
     * Generate SQL from a SchemaComparator diff.
     */
    public function generate(array $diff): array
    {
        $sql = [
            'safe'        => [],
            'destructive' => [],
        ];

        // CREATE TABLE
        foreach ($diff['tables_to_create'] as $table => $columns) {
            $sql['safe'][] = $this->generateCreateTable($table, $columns);
            // Generate ADD CONSTRAINT for FK columns in new tables
            foreach ($columns as $colName => $colDef) {
                if (isset($colDef['fk'])) {
                    $sql['safe'][] = $this->generateAddConstraint($table, $colName, $colDef);
                }
            }
        }

        // DROP TABLE — destructive
        foreach ($diff['tables_to_drop'] as $table) {
            $t = $this->wrapIdentifier($table);
            $sql['destructive'][] = "DROP TABLE {$t};";
        }

        // ADD COLUMN
        foreach ($diff['columns_to_add'] as $table => $columns) {
            foreach ($columns as $colName => $colDef) {
                $sql['safe'][] = $this->generateAddColumn($table, $colName, $colDef);
                // Add FK constraint if this new column has one
                if (isset($colDef['fk'])) {
                    $sql['safe'][] = $this->generateAddConstraint($table, $colName, $colDef);
                }
            }
        }

        // DROP COLUMN — destructive
        foreach ($diff['columns_to_drop'] as $table => $columns) {
            foreach ($columns as $colName) {
                $t = $this->wrapIdentifier($table);
                $c = $this->wrapIdentifier($colName);
                $sql['destructive'][] = "ALTER TABLE {$t} DROP COLUMN {$c};";
            }
        }

        // ALTER COLUMN
        foreach ($diff['columns_to_alter'] as $table => $columns) {
            foreach ($columns as $colName => $changes) {
                $sql['safe'][] = $this->generateAlterColumn($table, $colName, $changes);
            }
        }

        // ADD FOREIGN KEY CONSTRAINT
        foreach ($diff['constraints_to_add'] ?? [] as $table => $constraints) {
            foreach ($constraints as $colName => $constraintDef) {
                $sql['safe'][] = $this->generateAddConstraint($table, $colName, $constraintDef);
            }
        }

        return $sql;
    }

    /**
     * Generate a full CREATE TABLE statement.
     */
    protected function generateCreateTable(string $table, array $columns): string
    {
        $t = $this->wrapIdentifier($table);
        $lines = [];

        foreach ($columns as $colName => $colDef) {
            $lines[] = '    ' . $this->buildColumnDefinition($colName, $colDef);
        }

        if (array_key_exists('id', $columns)) {
            $id = $this->wrapIdentifier('id');
            $lines[] = "    PRIMARY KEY ({$id})";
        }

        $body = implode(",\n", $lines);
        return "CREATE TABLE {$t} (\n{$body}\n);";
    }

    /**
     * Generate an ALTER TABLE ... ADD COLUMN statement.
     */
    protected function generateAddColumn(string $table, string $colName, array $colDef): string
    {
        $t = $this->wrapIdentifier($table);
        $definition = $this->buildColumnDefinition($colName, $colDef);
        return "ALTER TABLE {$t} ADD COLUMN {$definition};";
    }

    /**
     * Generate an ALTER COLUMN statement.
     * Delegated to each driver as syntax differs significantly.
     */
    abstract protected function generateAlterColumn(string $table, string $colName, array $changes): string;

    /**
     * Build a single column definition string.
     */
    protected function buildColumnDefinition(string $colName, array $colDef): string
    {
        $type = $colDef['type']     ?? 'string';
        $nullable = $colDef['nullable'] ?? false;

        $c = $this->wrapIdentifier($colName);
        $sqlType  = $this->toSQLType($colName, $type);
        $nullPart = $nullable ? 'NULL' : 'NOT NULL';

        if ($colName === 'id' && $type === 'int') {
            return "{$c} {$sqlType} NOT NULL " . $this->autoIncrementKeyword();
        }

        return "{$c} {$sqlType} {$nullPart}";
    }

    /**
     * Generate an ALTER TABLE ... ADD CONSTRAINT FOREIGN KEY statement.
     */
    abstract protected function generateAddConstraint(string $table, string $colName, array $constraintDef): string;

    /**
     * Keyword used for auto-increment columns.
     * Overridden per driver.
     */
    abstract protected function autoIncrementKeyword(): string;
}
