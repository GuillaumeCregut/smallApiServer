<?php

use App\Security\User;
use PHPUnit\Framework\TestCase;

use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Interfaces\Databases\EntityInterface;
use App\Kernel\Interfaces\Databases\ConnectorInterface;

include 'entity2.php';
include 'EntityToCreate.php';
class AbstractRepositoryTest extends TestCase
{
    public function testWithNotEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $this->expectException(DatabaseException::class);
        $entity = new class extends AbstractRepository {
            protected ?string $entity = "NonExistentEntity";
        };
    }

    public function testDeleteWithWrongEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $entity2 = new Entity2();
        ConnectorDispatcher::setConnector($connector);
        $this->expectException(DatabaseException::class);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = User::class;
        };
        $this->expectException(DatabaseException::class);
        $repository->delete($entity2);
    }

    public function testSaveWithWrongEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $entity2 = new Entity2();
        ConnectorDispatcher::setConnector($connector);
        $this->expectException(DatabaseException::class);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = User::class;
        };
        $this->expectException(DatabaseException::class);
        $repository->save($entity2);
    }

    public function testGetTableName(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = User::class;
        };
        $this->assertEquals('user', $repository->getTableName());
    }

    public function testCreateSqlCreateTable(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $sql = 'CREATE TABLE entity_to_create (id INT NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, age INT NULL, PRIMARY KEY (id))';
        $this->assertEquals($sql, $repository->createSqlTable());
    }
}
