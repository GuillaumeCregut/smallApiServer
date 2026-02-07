<?php

namespace App\Kernel\Connector;

use App\Kernel\Interfaces\Databases\EntityInterface;
use App\Kernel\Interfaces\Databases\RepositoryInterface;

abstract class AbstractEntity implements EntityInterface
{
    protected ?int $id = null;
    protected string $repositoryClass;
    protected RepositoryInterface $repository;

    public function __construct()
    {
        if(!is_subclass_of($this->repositoryClass, RepositoryInterface::class)) {
            throw new DatabaseException('Repository class must be an instance ofRepositoryInterface');
        }
        $this->repository=new $this->repositoryClass();
    }

    public function save(): self
    {
        //Todo : save entity
       // $this->repository->save($this);
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }
}