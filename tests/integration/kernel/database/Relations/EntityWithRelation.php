<?php

use App\Kernel\Connector\Datas\LazyBag;
use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\OneToMany;

class EntityWithRelation extends AbstractEntity
{
    #[NotStored]
    protected static ?string $repo = RepoWithRelation::class;

    #[OneToMany(targetEntity: EntityTwoRelation::class, mappedBy: 'user')]
    private ?LazyBag $posts = null;
    private ?string $name = null;
    private ?string $firstName = null;

    public function getName(): string
    {
        return $this->name;
    }
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function setPosts(LazyBag $posts): self
    {
        $this->posts = $posts;
        return $this;
    }

    public function getPosts(): ?LazyBag
    {
        return $this->posts;
    }
}
