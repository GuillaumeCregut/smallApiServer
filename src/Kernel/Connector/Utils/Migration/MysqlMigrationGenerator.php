<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

class MysqlMigrationGenerator extends AbstractMigrationGeneratorDriver
{
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
            'relation' => $changes['relation']['to'] ?? [],
        ];
 
        $t          = $this->wrapIdentifier($table);
        $definition = $this->buildColumnDefinition($colName, $colDef);
        return "ALTER TABLE {$t} MODIFY COLUMN {$definition};";
    }

    protected function toSQLType(string $colName, string $genericType): string
    {
        return match($genericType) {
            'int'      => 'INT',
            'string'   => 'VARCHAR(255)',
            'bool'     => 'TINYINT(1)',
            'float'    => 'DECIMAL(10,2)',
            'datetime' => 'DATETIME',
            'date'     => 'DATE',
            'time'     => 'TIME',
            'json'     => 'JSON',
            default    => 'VARCHAR(255)',
        };
    }
}