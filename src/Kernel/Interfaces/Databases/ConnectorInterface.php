<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Interfaces\Databases;


interface ConnectorInterface 
{
    public function getConnection(): mixed;
    public static function getInstance(): ConnectorInterface;
    public function executeQuery(string $sql, array $params=[]): bool | int;
    public function fetchQuery(string $sql, array $params=[]): array;
    public function FetchQueryOnce(string $sql, array $params = []): ?array;
    public function startTransac(): void;
    public function commitTransac(): void;
    public function rollBack(): void;
}
