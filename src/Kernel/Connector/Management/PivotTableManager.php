<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Management;

use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

class PivotTableManager
{
    public function __construct(private ConnectorInterface $connector) {}

    public static function getTableName(string $ownerTable, string $targetTable, ?ManyToMany $attr = null): string
    {
        if (null !== $attr) {
            if ('' !== $attr->pivotTable) {
                return $attr->pivotTable;
            }
        }
        $tables = [$ownerTable, $targetTable];
        sort($tables);
        return implode('_', $tables);
    }

    public function loadRelatedIds(
        string $pivotTable,
        string $ownerCol,
        int $ownerId
    ): array {
        $sql = "SELECT * FROM {$pivotTable} WHERE {$ownerCol} = :id";
        $rows = $this->connector->fetchQuery($sql, [':id' => $ownerId]);
        return array_map(function (array $row) use ($ownerCol) {
            unset($row[$ownerCol]);
            return (int) array_values($row)[0];
        }, $rows);
    }

    public function sync(
        string $pivotTable,
        string $ownerCol,
        string $targetCol,
        int $ownerId,
        array $currentIds
    ): void {
        $sql = "DELETE FROM {$pivotTable} WHERE {$ownerCol} = :id";
        $this->connector->executeQuery($sql, ['id' => $ownerId]); //OK

        foreach ($currentIds as $targetId) {
            $sql = "INSERT INTO {$pivotTable} ({$ownerCol},{$targetCol}) VALUES (:owner, :target)";
            $this->connector->executeQuery($sql, [
                ':owner' => $ownerId,
                ':target' => $targetId
            ]);
        }
    }
}
