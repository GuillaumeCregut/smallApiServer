<?php

use App\Security\User;

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\ConnectorDispatcher;

use App\Kernel\Interfaces\Databases\ConnectorInterface;

include 'entity2.php';
include 'EntityToCreate.php';
include 'EntityToUpdate.php';
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

    public function testFindAllQuerySql(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $sql = 'SELECT * FROM entity_to_create';
        $test = $repository->findAll();
        $this->assertEquals($sql, $repository->sql);
    }

    public function testFindAllFailQuery(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willThrowException(new DatabaseException());
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $this->expectException(DatabaseException::class);
        $test = $repository->findAll();
    }


    public function testFindAllQuery(): void
    {
        $values = [
            [
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
                'age' => 30
            ],
            [
                'id' => 2,
                'name' => 'Killin',
                'first_name' => 'Jane',
                'age' => 25
            ]
        ];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $result = $repository->findAll();
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertInstanceOf(EntityToCreate::class, $result[0]);
        /**
         * @var EntityTocreate $ent1 
         */
        $ent1 = $result[0];
        /**
         * @var EntityTocreate $ent2 
         */
        $ent2 = $result[1];
        $this->assertEquals(1, $ent1->getid());
        $this->assertEquals('Doe', $ent1->getName());
        $this->assertEquals('John', $ent1->getFirstName());
        $this->assertEquals(30, $ent1->getAge());
        $this->assertEquals(2, $ent2->getid());
        $this->assertEquals('Killin', $ent2->getName());
        $this->assertEquals('Jane', $ent2->getFirstName());
        $this->assertEquals(25, $ent2->getAge());
    }

    public function testFindAQuerySql(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $sql = 'SELECT * FROM entity_to_create WHERE id = ?';
        $test = $repository->find(1);
        $this->assertEquals($sql, $repository->sql);
    }

    public function testFindOneButMultipleResultQuery(): void
    {
        $values = [
            [
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
                'age' => 30
            ],
            [
                'id' => 2,
                'name' => 'Killin',
                'first_name' => 'Jane',
                'age' => 25
            ]
        ];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $this->expectException(DatabaseException::class);
        $result = $repository->find(1);
    }

    public function testFindNoResult(): void
    {
        $values = [];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $result = $repository->find(1);
        $this->assertNull($result);
    }

    public function testFind(): void
    {
        $values = [
            [
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
                'age' => 30
            ],
        ];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        /**
         * @var EntityTocreate $result 
         */
        $result = $repository->find(1);
        $this->assertEquals(1, $result->getid());
        $this->assertEquals('Doe', $result->getName());
        $this->assertEquals('John', $result->getFirstName());
        $this->assertEquals(30, $result->getAge());
    }

    public function testFindByOneParam(): void
    {
        $values = [
            [
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
                'age' => 30
            ],
        ];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $search =[
            'firstName' =>'John'
        ];
        $results = $repository->findBy($search);
        $query = 'SELECT * FROM entity_to_create WHERE first_name = ?';
        $this->assertEquals($query, $repository->sql);
        $this->assertEquals(1, count($results));
        /**
         * @var EntityTocreate $result 
         */
        $result = $results[0];
        $this->assertInstanceOf(EntityToCreate::class,$result);
        $this->assertEquals(1, $result->getid());
        $this->assertEquals('Doe', $result->getName());
        $this->assertEquals('John', $result->getFirstName());
        $this->assertEquals(30, $result->getAge());
    }

    public function testFindByTwoParam(): void
    {
        $values = [
            [
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
                'age' => 30
            ],
        ];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $search =[
            'firstName' =>'John',
            'name' =>'Doe'
        ];
        $results = $repository->findBy($search);
        $query = 'SELECT * FROM entity_to_create WHERE first_name = ? AND name = ?';
        $this->assertEquals($query, $repository->sql);
        $this->assertEquals(1, count($results));
        /**
         * @var EntityTocreate $result 
         */
        $result = $results[0];
        $this->assertInstanceOf(EntityToCreate::class,$result);
        $this->assertEquals(1, $result->getid());
        $this->assertEquals('Doe', $result->getName());
        $this->assertEquals('John', $result->getFirstName());
        $this->assertEquals(30, $result->getAge());
    }
    
    public function testInsertNewEntityFail(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturn(false);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $entity = new EntityToCreate();
        $entity->setName('Doe')
            ->setFirstName('John');
        $result = $repository->save($entity);
        $query = 'INSERT INTO entity_to_create (name, first_name) VALUES (?, ?)';
        $this->assertEquals( $query, $repository->sql);
        $this->assertNull($result);
    }

    public function testInsertNewEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturn(20);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $entity = new EntityToCreate();
        $entity->setName('Doe')
            ->setFirstName('John');
        $result = $repository->save($entity);
        $query = 'INSERT INTO entity_to_create (name, first_name) VALUES (?, ?)';
        $this->assertEquals( $query, $repository->sql);
        $this->assertEquals(20,$result->getId());
    }

     public function testUpdateEntityError(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturn(false);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToCreate::class;
        };
        $entity = new EntityToCreate();
        $entity->setName('Doe')
            ->setid(2)
            ->setFirstName('John');
        $result = $repository->save($entity);
        $query = 'UPDATE entity_to_create SET name = ?, first_name = ?, age = ? WHERE id = ?';
        $this->assertEquals($query, $repository->sql);
        $this->assertFalse($result);
    }

    public function testUpdateEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturn(true);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityToUpdate::class;
        };
        $entity = new EntityToUpdate();
        $entity->setName('Doe')
            ->setid(2)
            ->setFirstName('John');
        /**
         * @var EntityTocreate $result 
         */    
        $result = $repository->save($entity);
        $query = 'UPDATE entity_to_update SET name = ?, first_name = ?, age = ? WHERE id = ?';
        $this->assertEquals( $query, $repository->sql);
        $this->assertEquals(2,$result->getId());
        $this->assertEquals('Doe',$result->getName());
        $this->assertEquals('John',$result->getfirstName());
    }
}
