<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

use App\Kernel\Connector\Attributes\ManyToMany;
use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\OneToMany;
use App\Kernel\Connector\Interfaces\BagInterface;
use App\Kernel\Connector\Interfaces\EntityInterface;
use ReflectionClass;

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

    /**
     * Add an element to a OneToMany collection property and automatically
     * set the back-reference (ManyToOne) on the element.
     *
     * Usage in a concrete entity:
     *   public function addComment(CommentEntity $comment): self
     *   {
     *       $this->addToCollection('comments', $comment);
     *       return $this;
     *   }
     *
     * The developer never needs to call $comment->setPost($this) manually.
     */
    protected function addToCollection(string $propertyName, EntityInterface $element): void
    {
        $reflection = new \ReflectionClass($this);

        if (!$reflection->hasProperty($propertyName)) {
            return;
        }

        $property  = $reflection->getProperty($propertyName);
        $oneToMany = $property->getAttributes(OneToMany::class);

        if (!empty($oneToMany)) {
            /** @var OneToMany $relation */
            $relation = $oneToMany[0]->newInstance();
            $setter   = 'set' . ucfirst($relation->mappedBy);
            if (method_exists($element, $setter)) {
                $element->$setter($this); // sets FK back-reference automatically
            }
        }

        $bag = $property->getValue($this);
        if ($bag instanceof BagInterface) {
            /**@var LazyBag $bag */
            $bag->addWithoutInitializing($element);
        }
    }

    protected function removeFromManyToMany(string $propertyName, EntityInterface $element): void
    {
        $reflection = new ReflectionClass($this);
        if (!$reflection->hasProperty($propertyName)) {
            return;
        }
        $property   = $reflection->getProperty($propertyName);
        $manyToMany = $property->getAttributes(ManyToMany::class);
        if (empty($manyToMany)) {
            return;
        }

        /** @var LazyBag $bag */
        $bag = $property->getValue($this);
        if ($bag instanceof BagInterface) {
            $bag->removeWithoutInitializing($element);
        }

        $relation = $manyToMany[0]->newInstance();
        if ('' === $relation->inversedBy) {
            return;
        }

        $inverseReflection = new ReflectionClass($element);
        if (!$inverseReflection->hasProperty($relation->inversedBy)) {
            return;
        }

        $inverseProperty = $inverseReflection->getProperty($relation->inversedBy);
        $inverseBag      = $inverseProperty->getValue($element);
        /** @var LazyBag $inverseBag */
        if ($inverseBag instanceof BagInterface) {
            $inverseBag->removeWithoutInitializing($this);
        }
    }

    protected function addToManyToMany(string $propertyName, EntityInterface $element): void
    {
        $reflection = new ReflectionClass($this);
        if (!$reflection->hasProperty($propertyName)) {
            return;
        }
        $property = $reflection->getProperty($propertyName);
        $manyToMany = $property->getAttributes(ManyToMany::class);
        if (empty($manyToMany)) {
            return;
        }
        /**@var LazyBag $bag */
        $bag = $property->getValue($this);
        if ($bag instanceof BagInterface) {
            $bag->addWithoutInitializing($element);
        }
        $relation = $manyToMany[0]->newInstance();
        if ('' === $relation->inversedBy) {
            return;
        }

        $inverseReflection = new \ReflectionClass($element);
        if (!$inverseReflection->hasProperty($relation->inversedBy)) {
            return;
        }

        $inverseProperty = $inverseReflection->getProperty($relation->inversedBy);
        $inverseBag      = $inverseProperty->getValue($element);
        /**@var LazyBag $inverseBag */
        if ($inverseBag instanceof BagInterface) {
            $inverseBag->addWithoutInitializing($this);
        }
    }
}
