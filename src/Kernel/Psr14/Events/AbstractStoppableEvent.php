<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Psr14\Events;

use App\Kernel\Interfaces\Psr14\StoppableEventInterface;

abstract class AbstractStoppableEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;
    private array $bag=[];
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void 
    {
        $this->propagationStopped = true;
    }

    public function getBag(): array
    {
        return $this->bag;
    }

    public function getFromBag(string $key, mixed $default=null): mixed
    {
        return $this->bag[$key] ?? $default;
    }
    public function addInBag(string $key, mixed $value): self
    {
        $this->bag[$key] = $value;
        return $this;
    }

    public function removeFromBag(string $key): self
    {
        if(array_key_exists($key,$this->bag)) {
            unset($this->bag[$key]);
        }
        return $this;
    }

    public function hasInBag(string $key): bool
    {
        return array_key_exists($key, $this->bag);
    }

}
