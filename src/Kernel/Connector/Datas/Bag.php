<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Datas;

use Traversable;
use ArrayIterator;
use App\Kernel\Connector\Interfaces\BagInterface;

class Bag implements BagInterface
{

    /** @var array<int, T> */
    protected array $elements;

    public function __construct(array $elements = [])
    {
        $this->elements = array_values($elements);
    }

    public function add(mixed $element): self
    {
        $this->elements[] = $element;
        return $this;
    }

    public function remove(mixed $element): bool
    {
        $key = array_search($element, $this->elements, true);
        if ($key === false) {
            return false;
        }
        unset($this->elements[$key]);
        $this->elements = array_values($this->elements);
        return true;
    }

    public function contains(mixed $element): bool
    {
        return in_array($element, $this->elements, true);
    }
    public function count(): int
    {
        return count($this->elements);
    }

    public function isEmpty(): bool
    {
        return empty($this->elements);
    }

    public function toArray(): array
    {
        return $this->elements;
    }

    public function filter(callable $predicate): static
    {
        return new static(
            array_values(
                array_filter($this->elements, $predicate)
            )
        );
    }

    public function map(callable $fn): BagInterface
    {
        return new Bag(
            array_map($fn, $this->elements)
        );
    }


    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }
}
