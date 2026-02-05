<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Hydrator;

include('Entity.php');


class HydratorTest extends TestCase
{
        public function testHydratorEntityOK(): void
        {
            $values = [
                'name'=>'Doe',
                'firstname'=>'John',
                'age'=>30
            ];
            /**
             * @var Entity $entity
             */
            $entity = Hydrator::hydrate(new Entity(),$values);
            $this->assertInstanceOf(Entity::class, $entity);
            $this->assertEquals('John', $entity->getFirstname()); 
            $this->assertEquals('Doe', $entity->getName()); 
            $this->assertEquals(30, $entity->getAge()); 
        }

         public function testHydratorEntityWithMore(): void
        {
            $values = [
                'name'=>'Doe',
                'firstname'=>'John',
                'age'=>30,
                'mail'=>'john.doe@example.com'
            ];
            /**
             * @var Entity $entity
             */
            $entity = Hydrator::hydrate(new Entity(),$values);
            $this->assertInstanceOf(Entity::class, $entity);
            $this->assertEquals('John', $entity->getFirstname()); 
            $this->assertEquals('Doe', $entity->getName()); 
            $this->assertEquals(30, $entity->getAge()); 
        }
}