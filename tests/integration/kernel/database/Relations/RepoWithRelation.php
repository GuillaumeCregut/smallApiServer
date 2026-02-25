<?php

use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\Interfaces\RepositoryInterface;

class RepoWithRelation extends AbstractRepository
{
    protected ?string $entity = EntityWithRelation::class;

    public function findBy(array $fields): array
    {
        return [];
    }
}