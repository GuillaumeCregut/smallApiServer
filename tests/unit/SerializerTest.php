<?php

use App\Kernel\utils\Serializer;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    public function testGetAllDatas(): void
    {
        $obj1 = new mock1();
        $expect = [
            'name' => 'Doe',
            'firstname' => 'John',
            'password' => '1234'
        ];
        $serialized = Serializer::serialize($obj1);
        $this->assertSame($expect, $serialized);
    }

    public function testGetDatasRestricted(): void
    {
        $obj1 = new mock1();
        $expect = [
            'name' => 'Doe',
            'firstname' => 'John',
        ];
        $serialized = Serializer::serialize($obj1, [mock1::class =>['password']]);
        $this->assertSame($expect, $serialized);
    }

    public function testNotGetPrivateParams(): void
    {
        $obj2 = new mock2();
        $expect = [
            'name' => 'Does',
            'firstname' => 'Jane',
        ];
        $serialized = Serializer::serialize($obj2);
        $this->assertSame($expect, $serialized);
    }

    public function testGetMultiStrate(): void
    {
        $obj2 = new mock2();
        $obj1 = new mock1();
        $obj3 = new mock3($obj1, $obj2);
        $expect = [
            'obj1' => [
                'name' => 'Doe',
                'firstname' => 'John',
                'password' => '1234'
            ],
            'obj2' => [
                'name' => 'Does',
                'firstname' => 'Jane',
            ],
        ];
        $serialized = Serializer::serialize($obj3);
        $this->assertSame($expect, $serialized);
    }

    public function testGetMultiStrateLimited(): void
    {
        $obj2 = new mock2();
        $obj1 = new mock1();
        $obj3 = new mock3($obj1, $obj2);
        $expect = [
            'obj1'=>null ,
            'obj2'=>null ,
        ];
        $serialized = Serializer::serialize($obj3,[], 0, 0);
        $this->assertSame($expect, $serialized); 
    }

    public function testUnShowOnSpecificClass(): void
    {
        $obj1 = new mock1();
        $obj2 = new mock2();
        $obj3 = new mock3($obj1, $obj2);
        $expect = [
            'obj1' => [
                'name' => 'Doe',
                'firstname' => 'John',
            ],
            'obj2' => [
                'name' => 'Does',
                'firstname' => 'Jane',
            ],
        ];
        $serialized = Serializer::serialize($obj3, [mock1::class => ['password']]);
        $this->assertSame($expect, $serialized);
    }

   public function testUnShowWithInheritance(): void
    {
        $obj8 = new mock8();
        $expect = [
            'name' => 'Doe',
            'firstname' => 'John',
            // password hidden via mock1::class rule, applied to mock8 which extends mock1
        ];
        $serialized = Serializer::serialize($obj8, [mock1::class => ['password']]);
        $this->assertSame($expect, $serialized);
    }

    public function testArrayOfObjects(): void
    {
        $obj1 = new mock1();
        $obj2 = new mock2();
        $obj4 = new mock4([$obj1, $obj2]);
        $expect = [
            'items' => [
                [
                    'name' => 'Doe',
                    'firstname' => 'John',
                    'password' => '1234',
                ],
                [
                    'name' => 'Does',
                    'firstname' => 'Jane',
                ],
            ],
        ];
        $serialized = Serializer::serialize($obj4);
        $this->assertSame($expect, $serialized);
    }
   

    public function testArrayOfObjectsBeyondMaxDepth(): void
    {
        $obj1 = new mock1();
        $obj2 = new mock2();
        $obj4 = new mock4([$obj1, $obj2]);
        $expect = [
            'items' => [
                null,
                null,
            ],
        ];
        $serialized = Serializer::serialize($obj4, [], 0, 0);
        $this->assertSame($expect, $serialized);
    }

    public function testEmptyObject(): void
    {
        $obj5 = new mock5();
        $expect = [];
        $serialized = Serializer::serialize($obj5);
        $this->assertSame($expect, $serialized);
    }

    public function testUninitializedProperty(): void
    {
        $obj6 = new mock6();
        $expect = [
            'name' => 'Doe',
            // uninitialized 'firstname' is skipped
        ];
        $serialized = Serializer::serialize($obj6);
        $this->assertSame($expect, $serialized);
    }

    public function testMixedVisibility(): void
    {
        $obj7 = new mock7();
        $expect = [
            'name' => 'Doe',       // public
            'firstname' => 'John', // private with getter
        ];
        $serialized = Serializer::serialize($obj7);
        $this->assertSame($expect, $serialized);
    }

    public function testPublicObjectBeyondMaxDepth(): void
    {
        $obj1 = new mock1();
        $obj7 = new mock7($obj1);
        $expect = [
            'name' => null,        // public object property, beyond maxDepth
            'firstname' => 'John',
        ];
        $serialized = Serializer::serialize($obj7, [], 0, 0);
        $this->assertSame($expect, $serialized);
    }

     public function testDeepNesting(): void
    {
        $obj1 = new mock1();
        $obj2 = new mock2();
        $obj3 = new mock3($obj1, $obj2);
        $obj9 = new mock9($obj3);
        $expect = [
            'obj3' => [
                'obj1' => [
                    'name' => 'Doe',
                    'firstname' => 'John',
                    'password' => '1234',
                ],
                'obj2' => [
                    'name' => 'Does',
                    'firstname' => 'Jane',
                ],
            ],
        ];
        $serialized = Serializer::serialize($obj9, [], 0, 2);
        $this->assertSame($expect, $serialized);
    }
}

class mock1
{
    private string $name = 'Doe';
    private string $firstname = 'John';
    private string $password = '1234';

    public function getName(): string
    {
        return $this->name;
    }
    public function getFirstname(): string
    {
        return $this->firstname;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
}


class mock2
{
    private string $name = 'Does';
    private string $firstname = 'Jane';
    private string $password = '1234';

    public function getName(): string
    {
        return $this->name;
    }
    public function getFirstname(): string
    {
        return $this->firstname;
    }
}

class mock3
{
    private Mock1 $obj1;
    private Mock2 $obj2;

    public function __construct(Mock1 $ob1, Mock2 $obj2)
    {
        $this->obj1 = $ob1;
        $this->obj2 = $obj2;
    }


    public function getObj1(): Mock1
    {
        return $this->obj1;
    }

    public function getObj2(): Mock2
    {
        return $this->obj2;
    }
}

class mock4
{
    private array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getItems(): array { return $this->items; }
}

class mock5
{
    // empty object, no properties
}

class mock6
{
    private string $name = 'Doe';
    private string $firstname; // uninitialized

    public function getName(): string { return $this->name; }
    public function getFirstname(): string { return $this->firstname; }
}

class mock7
{
    public mixed $name;
    private string $firstname = 'John';

    public function __construct(mixed $name = 'Doe')
    {
        $this->name = $name;
    }

    public function getFirstname(): string { return $this->firstname; }
}

// extends mock1 — inherits all fields and getters
class mock8 extends mock1
{
}

class mock9
{
    private mock3 $obj3;

    public function __construct(mock3 $obj3)
    {
        $this->obj3 = $obj3;
    }

    public function getObj3(): mock3 { return $this->obj3; }
}