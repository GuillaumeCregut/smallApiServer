<?php

use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\Interfaces\RepositoryInterface;

class RepoTwoRelation extends AbstractRepository
{
    protected ?string $entity = EntityTwoRelation::class;

    public function findBy(array $fields): array
    {
        return [];
    }
}