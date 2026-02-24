<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector\Interfaces;

use Countable;
use IteratorAggregate;

interface BagInterface extends Countable, IteratorAggregate
{
    public function add(mixed $element): self;

    public function remove(mixed $element): bool;

    public function contains(mixed $element): bool;

    public function isEmpty(): bool;

    public function toArray(): array;

    public function filter(callable $predicate): static;

    public function map(callable $fn): BagInterface;
    
}