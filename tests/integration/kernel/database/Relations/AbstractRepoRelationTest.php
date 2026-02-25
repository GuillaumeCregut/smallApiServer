<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Datas\LazyBag;
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
}