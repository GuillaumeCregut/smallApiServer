<?php

use App\Kernel\Request;
use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Hydrator;

include 'entity2.php';
class Request2EntityTest extends TestCase
{
    public function testEntityFromRequest(): void
    {
        Request::resetInstance();
        $post = [
            'name' => 'Doe',
            'firstname' => 'John',
            'age' => 30
        ];
        $request = Request::initInstance([], [], [], $post, [], [], []);
        /**
         * @var Entity $entity
         */
        $entity = Hydrator::hydrate(new Entity2(), $request->getAllDatas());
        $this->assertInstanceOf(Entity2::class, $entity);
        $this->assertEquals('John', $entity->getFirstname());
        $this->assertEquals('Doe', $entity->getName());
        $this->assertEquals(30, $entity->getAge());
    }
}
