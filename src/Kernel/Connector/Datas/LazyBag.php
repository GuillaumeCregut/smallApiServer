<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Datas;

use Closure;
use Traversable;
use ArrayIterator;
use App\Kernel\Connector\Datas\Bag;
use App\Kernel\Connector\Interfaces\BagInterface;

final class LazyBag extends Bag
{

    private bool $initialized = false;
    private Closure $loader;

    public function __construct(Closure $loader)
    {
        parent::__construct([]);
        $this->loader = $loader;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function add(mixed $element): self
    {
        $this->initialize();
        return parent::add($element);
    }

    public function remove(mixed $element): bool
    {
       $this->initialize(); 
       return parent::remove($element);
    }

    public function contains(mixed $element): bool
    {
        $this->initialize();
        return parent::contains($element);
    }
    public function count(): int
    {
        $this->initialize();
        return parent::count();
    }

    public function isEmpty(): bool
    {
        $this->initialize();
        return parent::isEmpty();
    }

    public function toArray(): array
    {
        $this->initialize();
        return parent::toArray();
    }

    public function filter(callable $predicate): static
    {
        $this->initialize();
        return parent::filter($predicate);
    }

    public function map(callable $fn): BagInterface
    {
        $this->initialize();
        return parent::map($fn);
    }

    public function getIterator(): Traversable
    {
        $this->initialize();
        return parent::getIterator();
    }

    private function initialize(): void
    {
        if($this->initialized) {
            return;
        }
        $this->elements = ($this->loader)();
        $this->initialized = true;
    }
}
