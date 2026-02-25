<?php

use App\Kernel\Connector\AbstractEntity;
use App\Kernel\Connector\Attributes\ManyToOne;
use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Datas\LazyBag;

class EntityTwoRelation extends AbstractEntity
{
    #[NotStored]
    protected static ?string $repo = RepoTwoRelation::class;

    #[ManyToOne(targetEntity: EntityWithRelation::class, inversedBy: 'userId')]
    private ?int $userId = null;
    private ?string $title = null;
    private ?string $content = null;

    
}
