<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\DatabaseException;

class EntityTest extends TestCase
{
    public function testWithBadRepositoryClass(): void
    {
        $this->expectException(DatabaseException::class);
        $entity = new class extends AbstractEntity {
            protected string $repositoryClass = 'NonExistentRepository';
        };
    }
}