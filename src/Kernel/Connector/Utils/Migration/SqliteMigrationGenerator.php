<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

class SqliteMigrationGenerator extends AbstractMigrationGeneratorDriver
{
    protected function wrapIdentifier(string $name): string
    {
        return "\"{$name}\"";
    }

    protected function autoIncrementKeyword(): string
    {
        return 'AUTOINCREMENT';
    }

    /**
     * SQLite does NOT support ALTER COLUMN or MODIFY COLUMN.
     * The only way to change a column is to recreate the table.
     * We generate a comment warning the dev to handle this manually.
     */
    protected function generateAlterColumn(string $table, string $colName, array $changes): string
    {
        $changesDesc = json_encode($changes, JSON_PRETTY_PRINT);
        return implode("\n", [
            "-- WARNING: SQLite does not support ALTER COLUMN.",
            "-- Column \"{$colName}\" in table \"{$table}\" requires manual migration.",
            "-- Changes detected: {$changesDesc}",
            "-- To apply: recreate the table with the new schema and copy the data.",
        ]);
    }

    protected function toSQLType(string $colName, string $genericType): string
    {
        return match ($genericType) {
            'int' => 'INTEGER',
            'string' => 'TEXT',
            'bool' => 'INTEGER',  // SQLite has no native bool
            'float' => 'REAL',
            'datetime' => 'TEXT',     // SQLite has no native datetime
            'date' => 'TEXT',
            'time' => 'TEXT',
            'json' => 'TEXT',     // SQLite has no native JSON type
            default => 'TEXT',
        };
    }
}
