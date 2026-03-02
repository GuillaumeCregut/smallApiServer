<?php

use App\Security\User;
use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Management\RepositoryFactory;
use App\Kernel\Connector\Interfaces\ConnectorInterface;
use App\Kernel\Connector\Interfaces\RepositoryInterface;

class RepositoryFactoryTest extends TestCase
{
    public function testWithNotEntity(): void
    {
        $entity = $this->createStub(stdClass::class);
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Entity must implements EntityInterface');
        RepositoryFactory::getRepository(get_class($entity));
    }

    public function testRepositoryWithNull(): void
    {
        $entity = new class extends AbstractEntity {
            #[NotStored]
            protected static ?string $repo = null;
        };
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Entity must have valid Repository');
        RepositoryFactory::getRepository(get_class($entity));
    }

    public function testWithValidClass(): void
    {
        $connector = $this->createStub(ConnectorInterface::class);
        ConnectorDispatcher::setConnector($connector);
        $repo = RepositoryFactory::getRepository(User::class);
        $this->assertInstanceOf(RepositoryInterface::class,$repo);
        $this->assertSame('App\Security\UserRepository', get_class($repo));
    }
}
