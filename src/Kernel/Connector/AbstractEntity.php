<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\Interfaces\BagInterface;
use App\Kernel\Connector\Interfaces\EntityInterface;

abstract class AbstractEntity implements EntityInterface
{
    protected ?int $id = null;
    #[NotStored]
    protected static ?string $repo = null;
    public function __construct() {}


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public static function getRepository(): ?string
    {
        return static::$repo;
    }

    protected function syncRelation(string $propertyName, ?object $targetEntity): void
    {
        if (null === $targetEntity) {
            return;
        }

        $reflection = new \ReflectionClass($this);

        if (!$reflection->hasProperty($propertyName)) {
            return;
        }

        $property = $reflection->getProperty($propertyName);
        $attributes = $property->getAttributes(ManyToOne::class);

        if (empty($attributes)) {
            return;
        }

        /** @var ManyToOne $manyToOne */
        $manyToOne = $attributes[0]->newInstance();
        $inversedBy = $manyToOne->inversedBy;
        $getter = 'get' . ucfirst($inversedBy);

        if (!method_exists($targetEntity, $getter)) {
            return;
        }

        $bag = $targetEntity->$getter();

        if (!$bag instanceof BagInterface) {
            return;
        }
        /**@var LazyBag $bag */
        $bag->addWithoutInitializing($this);
    }
}
