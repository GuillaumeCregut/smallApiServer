<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Interfaces\ConnectorInterface;

include __DIR__ . DIRECTORY_SEPARATOR . "EntityWithRelation.php";
include __DIR__ . DIRECTORY_SEPARATOR . "EntityTwoRelation.php";
include __DIR__ . DIRECTORY_SEPARATOR . "RepoTwoRelation.php";
include __DIR__ . DIRECTORY_SEPARATOR . "RepoWithRelation.php";

class AbstractRepoRelationTest extends TestCase
{
    protected function setUp(): void
    {
        ConnectorDispatcher::resetConnector();
        parent::setUp();
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
    }

    public function testCreateSqlCreateTableWithOne2Many(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityWithRelation::class;
        };

        $sql = 'CREATE TABLE entity_with_relations (id INT NOT NULL AUTO_INCREMENT , name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, PRIMARY KEY (id))';
        $this->assertEquals($sql, $repository->createSqlTable());
    }

    public function testCreateSqlCreateTableWithMany2One(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repository = new class extends AbstractRepository {
            protected ?string $entity = EntityTwoRelation::class;
        };

        $sql = 'CREATE TABLE entity_two_relations (id INT NOT NULL AUTO_INCREMENT , user_id INT NOT NULL, title VARCHAR(255) NOT NULL, content VARCHAR(255) NOT NULL, PRIMARY KEY (id))';
        $this->assertEquals($sql, $repository->createSqlTable());
    }

    public function testBagIsSet(): void
    {
        ConnectorDispatcher::resetConnector();
        $values = [
            [
                'id' => 1,
                'name' => 'Doe',
                'first_name' => 'John',
            ],
        ];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoWithRelation();
        /**@var EntityWithRelation $result */
        $result = $repository->find(1);
        $this->assertEquals(1, $result->getid());
        $this->assertEquals('Doe', $result->getName());
        $this->assertEquals('John', $result->getFirstName());
        $this->assertInstanceOf(LazyBag::class, $result->getPosts());
    }

    public function testFindByOneParam(): void
    {
        $values = [
            [
                'id' => 1,
                'user_id' => 2,
                'title' => 'John',
                'content' => 'This is content'
            ],
        ];
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('fetchQuery')
            ->willReturn($values);
        ConnectorDispatcher::setConnector($connector);
        $repository = new RepoTwoRelation();
        /**
         * @var EntityTwoRelation $result 
         */
        $result = $repository->find(1);
        $query = 'SELECT * FROM entity_two_relations WHERE id = ?';
        $this->assertEquals($query, $repository->sql);
        $this->assertInstanceOf(EntityTwoRelation::class, $result);
        /**
         * @var EntityTwoRelation $result 
         */
        $this->assertEquals(1, $result->getid());
        $this->assertEquals('This is content', $result->getContent());
        $this->assertEquals('John', $result->getTitle());
        $this->assertInstanceOf(EntityWithRelation::class, $result->getUser());
    }

    public function testInsertNewEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturn(20);
        ConnectorDispatcher::setConnector($connector);
        $user = new EntityWithRelation();
        $user->setId(15);
        $repository = new RepoTwoRelation();
        $entity = new EntityTwoRelation();
        $entity->setTitle('MyTitle')
            ->setContent('John')
            ->setUser($user);
        $result = $repository->save($entity);
        $query = 'INSERT INTO entity_two_relations (user_id, title, content) VALUES (?, ?, ?)';
        $this->assertEquals($query, $repository->sql);
        $this->assertEquals(20, $result->getId());
        $params = [
            0 => 15,
            1 => 'MyTitle',
            2 => 'John'
        ];
        $this->assertEquals($params, $repository->params);
    }

    public function testUpdateEntity(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('executeQuery')
            ->willReturn(10);
        ConnectorDispatcher::setConnector($connector);
        $user = new EntityWithRelation();
        $user->setId(15);
        $repository = new RepoTwoRelation();
        $entity = new EntityTwoRelation();
        $entity->setTitle('MyTitle')
            ->setContent('John')
            ->setUser($user);
        $entity->setId(10);
        $result = $repository->save($entity);
        $query = 'UPDATE entity_two_relations SET user_id = ?, title = ?, content = ? WHERE id = ?';
        $this->assertEquals($query, $repository->sql);
        $this->assertEquals(10, $result->getId());
         $params = [
            0 => 15,
            1 => 'MyTitle',
            2 => 'John',
            3 => 10
        ];
        $this->assertEquals($params, $repository->params);
    }
}
