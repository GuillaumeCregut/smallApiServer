<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Connector\Datas\Bag;

class BagTest extends TestCase
{
    public function testBagIsEmptyByDefault(): void
    {
        $bag = new Bag();

        $this->assertTrue($bag->isEmpty());
        $this->assertCount(0, $bag);
    }

    public function testAddElement(): void
    {
        $bag = new Bag();
        $bag->add('foo');
        $this->assertFalse($bag->isEmpty());
        $this->assertTrue($bag->contains('foo'));
        $this->assertCount(1, $bag);
    }

    public function testRemoveElement(): void
    {
        $bag = new Bag(['foo', 'bar']);

        $result = $bag->remove('foo');

        $this->assertTrue($result);
        $this->assertFalse($bag->contains('foo'));
        $this->assertCount(1, $bag);
    }

    public function testRemoveNonExistingElementReturnsFalse(): void
    {
        $bag = new Bag(['foo']);

        $result = $bag->remove('bar');

        $this->assertFalse($result);
        $this->assertCount(1, $bag);
    }

    public function testIterationWorks(): void
    {
        $bag = new Bag(['a', 'b', 'c']);

        $result = [];

        foreach ($bag as $item) {
            $result[] = $item;
        }

        $this->assertSame(['a', 'b', 'c'], $result);
    }

    public function testFilterReturnsNewBag(): void
    {
        $bag = new Bag([1, 2, 3, 4]);

        $filtered = $bag->filter(
            fn(int $i) => $i % 2 === 0
        );

        $this->assertInstanceOf(Bag::class, $filtered);
        $this->assertSame([2, 4], $filtered->toArray());

        // Original unchanged
        $this->assertSame([1,2,3,4], $bag->toArray());
    }

    public function testMapTransformsElements(): void
    {
        $bag = new Bag([1, 2, 3]);

        $mapped = $bag->map(
            fn(int $i) => $i * 10
        );

        $this->assertSame([10, 20, 30], $mapped->toArray());
    }

    public function testContainsUsesStrictComparison(): void
    {
        $bag = new Bag([1]);

        $this->assertFalse($bag->contains('1'));
        $this->assertTrue($bag->contains(1));
    }
}
