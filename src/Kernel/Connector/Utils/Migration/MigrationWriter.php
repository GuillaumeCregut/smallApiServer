<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Utils\Migration;

use \Exception;

class MigrationWriter
{
    private string $migrationsPath;

    public function __construct(string $rootPath)
    {
        $this->migrationsPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .'migrations';
    }

    /**
     *  Write a migration file from generated SQL.
     * $sql is the output of MigrationGenerator::generate():
     * [
     *   'safe'        => [...],
     *   'destructive' => [...],
     * ]
     *
     * Returns the written file path, or null if nothing to migrate.
     *
     * @param array $sql
     * @return string|null
     */
    public function write(array $sql): ?string
    {
        $allUp = array_merge($sql['safe'], $sql['destructive']);
        if (empty($allUp)) {
            return null;
        }
        $version = $this->generateVersion();
        $className = "Version{$version}";
        $filePath = "{$this->migrationsPath}/{$className}.php";
        $upStatements = $this->renderStatements($allUp);
        $downStatements = $this->renderDownPlaceholder();
        $content = $this->renderClass($className, $upStatements, $downStatements);
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        $writed = file_put_contents($filePath, $content);
        if(false === $writed) {
            throw new Exception('Error, file not written');
        }
        return $filePath;
    }

    private function generateVersion(): string
    {
        return (new \DateTimeImmutable())->format('YmdHis');
    }

    private function renderStatements(array $statements): string
    {
        $lines = [];
        foreach ($statements as $statement) {
            $escaped = addslashes($statement);
            $lines[] = "        \$connector->executeQuery(\"{$escaped}\");";
        }
        return implode("\n", $lines);
    }

    /**
     * Render a commented placeholder for down() — user fills it in manually.
     * Destructive operations (DROP) are listed as comments for reference.
     */
    private function renderDownPlaceholder(): string
    {
        return "        // TODO: add rollback statements";
    }

    private function renderClass(string $className, string $upStatements, string $downStatements): string
    {
        return <<<PHP
<?php
 
/**
 * Auto-generated migration — {$className}
 * Review before running. Destructive operations (DROP) must be confirmed.
 */
 
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use Exception;

use App\Kernel\Connector\Interfaces\MigrationInterface;
 
class {$className} implements MigrationInterface
{
    public function up(ConnectorInterface \$connector): void
    {
{$upStatements}
    }
 
    public function down(ConnectorInterface \$connector): void
    {
{$downStatements}
    }
}
PHP;
    }
}
