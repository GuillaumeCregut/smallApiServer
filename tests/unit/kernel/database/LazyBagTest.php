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
        $this->assertSame(1, $callCount); 
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

    public function testIsNotDirtyOnConstruct(): void
    {
        $bag = new LazyBag(fn() => []);

        $this->assertFalse($bag->isDirty());
    }

    public function testAddMarksDirty(): void
    {
        $bag = new LazyBag(fn() => []);

        $bag->add($this->makePost(1));

        $this->assertTrue($bag->isDirty());
    }

    public function testRemoveMarksDirty(): void
    {
        $post = $this->makePost(1);
        $bag  = $this->makeLazyBag([$post]);

        $bag->remove($post);

        $this->assertTrue($bag->isDirty());
    }

    public function testAddWithoutInitializingMarksDirty(): void
    {
        $bag = new LazyBag(fn() => []);

        $bag->addWithoutInitializing($this->makePost(1));

        $this->assertTrue($bag->isDirty());
    }

    public function testRemoveWithoutInitializingMarksDirty(): void
    {
        $bag = new LazyBag(fn() => []);

        $bag->removeWithoutInitializing($this->makePost(1));

        $this->assertTrue($bag->isDirty());
    }

    public function testReadOnlyOperationsDoNotMarkDirty(): void
    {
        $post = $this->makePost(1);
        $bag  = $this->makeLazyBag([$post]);
 
        $bag->toArray();
        $bag->count();
        $bag->isEmpty();
        $bag->contains($post);
        $bag->get(0);
 
        $this->assertFalse($bag->isDirty());
    }

    public function testAddWithoutInitializingDoesNotInitialize(): void
    {
        $bag = new LazyBag(fn() => []);
 
        $bag->addWithoutInitializing($this->makePost(1));
 
        $this->assertFalse($bag->isInitialized());
    }
 
    public function testAddWithoutInitializingElementPresentAfterInit(): void
    {
        $post = $this->makePost(1);
        $bag  = new LazyBag(fn() => []);
 
        $bag->addWithoutInitializing($post);
 
        $this->assertContains($post, $bag->toArray());
    }
 
    public function testAddWithoutInitializingCombinesWithLoaderResult(): void
    {
        $existing = $this->makePost(1);
        $added    = $this->makePost(2);
        $bag      = $this->makeLazyBag([$existing]);
 
        $bag->addWithoutInitializing($added);
 
        $result = $bag->toArray();
        $this->assertContains($existing, $result);
        $this->assertContains($added, $result);
        $this->assertCount(2, $result);
    }

    public function testAddWithoutInitializingDoesNotAddDuplicateFromLoader(): void
    {
        $post = $this->makePost(1);
        $bag  = $this->makeLazyBag([$post]);
 
        $bag->addWithoutInitializing($post);
 
        $this->assertCount(1, $bag->toArray());
    }
 
    public function testAddWithoutInitializingAfterInitAddsDirectly(): void
    {
        $post = $this->makePost(2);
        $bag  = $this->makeLazyBag([$this->makePost(1)]);
        $bag->toArray(); // initialise
 
        $bag->addWithoutInitializing($post);
 
        $this->assertContains($post, $bag->toArray());
        $this->assertTrue($bag->isInitialized());
    }
 
    public function testAddWithoutInitializingMultipleElements(): void
    {
        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);
        $post3 = $this->makePost(3);
        $bag   = new LazyBag(fn() => []);
 
        $bag->addWithoutInitializing($post1);
        $bag->addWithoutInitializing($post2);
        $bag->addWithoutInitializing($post3);
 
        $result = $bag->toArray();
        $this->assertCount(3, $result);
        $this->assertContains($post1, $result);
        $this->assertContains($post2, $result);
        $this->assertContains($post3, $result);
    }

    public function testRemoveWithoutInitializingDoesNotInitialize(): void
    {
        $post = $this->makePost(1);
        $bag  = $this->makeLazyBag([$post]);
 
        $bag->removeWithoutInitializing($post);
 
        $this->assertFalse($bag->isInitialized());
    }
 
    public function testRemoveWithoutInitializingElementAbsentAfterInit(): void
    {
        $post = $this->makePost(1);
        $bag  = $this->makeLazyBag([$post]);
 
        $bag->removeWithoutInitializing($post);
 
        $this->assertNotContains($post, $bag->toArray());
    }
 
    public function testRemoveWithoutInitializingPreservesOtherElements(): void
    {
        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);
        $bag   = $this->makeLazyBag([$post1, $post2]);
 
        $bag->removeWithoutInitializing($post1);
 
        $result = $bag->toArray();
        $this->assertCount(1, $result);
        $this->assertNotContains($post1, $result);
        $this->assertContains($post2, $result);
    }

    public function testRemoveWithoutInitializingNonExistentElementLeavesOthers(): void
    {
        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);
        $bag   = $this->makeLazyBag([$post1]);
 
        $bag->removeWithoutInitializing($post2); // n'existe pas
 
        $this->assertCount(1, $bag->toArray());
        $this->assertContains($post1, $bag->toArray());
    }
 
    public function testRemoveWithoutInitializingAfterInitRemovesDirectly(): void
    {
        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);
        $bag   = $this->makeLazyBag([$post1, $post2]);
        $bag->toArray(); // initialise
 
        $bag->removeWithoutInitializing($post1);
 
        $this->assertNotContains($post1, $bag->toArray());
        $this->assertContains($post2, $bag->toArray());
    }

    public function testAddThenRemoveWithoutInitializingLeavesEmpty(): void
    {
        $post = $this->makePost(1);
        $bag  = new LazyBag(fn() => []);
 
        $bag->addWithoutInitializing($post);
        $bag->removeWithoutInitializing($post);
 
        $this->assertNotContains($post, $bag->toArray());
        $this->assertCount(0, $bag->toArray());
    }
 
    public function testRemoveThenAddWithoutInitializingElementIsPresent(): void
    {
        $post = $this->makePost(1);
        $bag  = new LazyBag(fn() => []);
 
        $bag->removeWithoutInitializing($post);
        $bag->addWithoutInitializing($post);
 
        $this->assertContains($post, $bag->toArray());
    }
 
    public function testAddWithoutInitializingThenRemoveFromLoaderResult(): void
    {
        $existing = $this->makePost(1);
        $added    = $this->makePost(2);
        $bag      = $this->makeLazyBag([$existing]);
 
        $bag->addWithoutInitializing($added);
        $bag->removeWithoutInitializing($existing);
 
        $result = $bag->toArray();
        $this->assertContains($added, $result);
        $this->assertNotContains($existing, $result);
        $this->assertCount(1, $result);
    }

    public function testWrapLoaderTransformsResult(): void
    {
        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);
        $post3 = $this->makePost(3);
        $bag   = $this->makeLazyBag([$post1, $post2, $post3]);
 
        $bag->wrapLoader(function (callable $previous): array {
            return array_slice(($previous)(), 0, 2);
        });
 
        $this->assertCount(2, $bag->toArray());
    }

    public function testWrapLoaderCanBeChained(): void
    {
        $post1 = $this->makePost(1);
        $post2 = $this->makePost(2);
        $post3 = $this->makePost(3);
        $bag   = $this->makeLazyBag([$post1, $post2, $post3]);
 
        
        $bag->wrapLoader(fn(callable $prev) => array_slice(($prev)(), 0, 2));
        
        $bag->wrapLoader(fn(callable $prev) => array_reverse(($prev)()));
 
        $result = $bag->toArray();
        $this->assertCount(2, $result);
        $this->assertSame($post2, $result[0]);
        $this->assertSame($post1, $result[1]);
    }
}
