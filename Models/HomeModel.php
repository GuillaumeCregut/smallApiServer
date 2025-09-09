<?php

namespace App\Models;

use App\Connector;

class HomeModel
{
    // Model code would go here
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        return [];
    }

    public function getOne(int $id): ?array
    {
        return null;
    }

    public function add(array $data): bool
    {
        return true;
    }

    public function update(int $id, array $data): bool
    {
        return true;
    }
    public function delete(int $id): bool
    {
        return true;
    }
}