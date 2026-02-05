<?php

namespace App\Kernel\Interfaces\Databases;


interface ConnectorInterface 
{
    public function getConnection(): mixed;
    public static function getInstance(): ConnectorInterface;
    public function executeQuery(string $sql, array $params=[]): bool;
    public function fetchQuery(string $sql, array $params=[]): array;
    public function FetchQueryOnce(string $sql, array $params = []): ?array;
    public function startTransac(): void;
    public function commitTransc(): void;
    public function rollBack(): void;
}
