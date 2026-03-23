<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

class MysqlMigrationGenerator extends AbstractMigrationGeneratorDriver
{
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
        return "CREATE TABLE {$t} (\n{$body}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    protected function wrapIdentifier(string $name): string
    {
        return "`{$name}`";
    }

    protected function autoIncrementKeyword(): string
    {
        return 'AUTO_INCREMENT';
    }

    protected function generateAlterColumn(string $table, string $colName, array $changes): string
    {
        $colDef = [
            'type'     => $changes['type']['to']     ?? null,
            'nullable' => $changes['nullable']['to'] ?? null,
        ];

        $t = $this->wrapIdentifier($table);
        $definition = $this->buildColumnDefinition($colName, $colDef);
        return "ALTER TABLE {$t} MODIFY COLUMN {$definition};";
    }

    protected function generateAddConstraint(string $table, string $colName, array $constraintDef): string
    {
        $t = $this->wrapIdentifier($table);
        $c = $this->wrapIdentifier($colName);
        $fkTable = $this->wrapIdentifier($constraintDef['fk']);
        $refCol = $this->wrapIdentifier('id');
        $constraintName = $this->wrapIdentifier('fk_' . $table . '_' . $colName);
        $onDelete = $constraintDef['onDelete'] ?? 'RESTRICT';
        $onUpdate = $constraintDef['onUpdate'] ?? 'RESTRICT';

        return "ALTER TABLE {$t} ADD CONSTRAINT {$constraintName} "
            . "FOREIGN KEY ({$c}) REFERENCES {$fkTable} ({$refCol}) "
            . "ON DELETE {$onDelete} ON UPDATE {$onUpdate};";
    }

    protected function toSQLType(string $colName, string $genericType): string
    {
        return match ($genericType) {
            'int' => 'INT',
            'string' => 'VARCHAR(255)',
            'bool' => 'TINYINT(1)',
            'float' => 'DECIMAL(10,2)',
            'datetime' => 'DATETIME',
            'date' => 'DATE',
            'time' => 'TIME',
            'json' => 'JSON',
            default => 'VARCHAR(255)',
        };
    }

    protected function generateCreatePivotTable(array $pivot): string
    {
        $t          = $this->wrapIdentifier($pivot['pivotTable']);
        $ownerCol   = $this->wrapIdentifier($pivot['ownerCol']);
        $targetCol  = $this->wrapIdentifier($pivot['targetCol']);
        $ownerTable = $this->wrapIdentifier($pivot['ownerTable']);
        $targetTable = $this->wrapIdentifier($pivot['targetTable']);
        $refId      = $this->wrapIdentifier('id');

        return "CREATE TABLE {$t} (\n"
            . "    {$ownerCol} INT NOT NULL,\n"
            . "    {$targetCol} INT NOT NULL,\n"
            . "    PRIMARY KEY ({$ownerCol}, {$targetCol}),\n"
            . "    FOREIGN KEY ({$ownerCol}) REFERENCES {$ownerTable} ({$refId}) ON DELETE CASCADE,\n"
            . "    FOREIGN KEY ({$targetCol}) REFERENCES {$targetTable} ({$refId}) ON DELETE CASCADE\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }
}
