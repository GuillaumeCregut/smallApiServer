<?php

use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\Interfaces\EntityInterface;
use PHPUnit\Framework\TestCase;

class LazyBagTest extends TestCase
{
    private function makePost(int $id): EntityInterface
    {
        $post = $this->createStub(AbstractEntity::class);
        $post->method('getId')->willReturn($id);
        return $post;
    }

    private function makeLazyBag(array $items): LazyBag
    {
        return new LazyBag(fn() => $items);
    }

    public function testInit(): void
    {
        $bag = new LazyBag(fn() => []);
        $this->assertFalse($bag->isInitialized());
    }

    public function testLoaderIsCalledOnlyOnce(): void
    {
        $callCount = 0;

        $bag = new LazyBag(function () use (&$callCount): array {
            $callCount++;
            return [$this->makePost(1)];
        });

        $bag->count();
        $bag->count();
        $bag->toArray();
        $this->assertTrue($bag->isInitialized());
        $this->assertSame(1, $callCount); // Le loader ne s'est exécuté qu'une fois
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $bag = $this->makeLazyBag([
            $this->makePost(1),
            $this->makePost(2),
        ]);

        $this->assertSame(2, $bag->count());
    }

    public function testIterable(): void
    {
        $posts = [
            $this->makePost(1),
            $this->makePost(2),
        ];
        $bag = $this->makeLazyBag($posts);
        $result = [];
        foreach ($bag as $post) {
            $result[] = $post->getId();
        }

        $this->assertSame([1, 2], $result);
    }
}
