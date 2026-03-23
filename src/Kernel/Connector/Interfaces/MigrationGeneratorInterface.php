<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Interfaces;

interface MigrationGeneratorInterface
{
    /**
     * Generate SQL statements from a SchemaComparator diff.
     *
     * Returns:
     * [
     *   'safe'        => [...],  // CREATE TABLE, ADD COLUMN, ALTER COLUMN
     *   'destructive' => [...],  // DROP TABLE, DROP COLUMN
     * ]
     */
    public function generate(array $diff): array;

    /**
     * Generate SQL from a PivotSchemaComparator diff.
     */
    public function generatePivot(array $pivotDiff): array;
}
