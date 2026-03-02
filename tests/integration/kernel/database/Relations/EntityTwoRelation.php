<?php

use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Datas\LazyBag;

class EntityTwoRelation extends AbstractEntity
{
    #[NotStored]
    protected static ?string $repo = RepoTwoRelation::class;

    #[ManyToOne(targetEntity: EntityWithRelation::class, inversedBy: 'posts')]
    private ?EntityWithRelation $user = null;
    private ?string $title = null;
    private ?string $content = null;

    

    /**
     * Get the value of title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set the value of title
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of content
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set the value of content
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser(): ?EntityWithRelation
    {
        return $this->user;
    }

    /**
     * Set the value of user
     */
    public function setUser(?EntityWithRelation $user): self
    {
        $this->user = $user;

        return $this;
    }
}
