<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Middleware\Security;

use App\Kernel\Connector\DatabaseException;
use App\Security\User;
use App\Kernel\Request;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Connector\Interfaces\RepositoryInterface;

class SessionAuthMiddleware implements AuthenticationInterface
{
    private ?User $user = null;
    private ?int $id = null;

    public function __construct(private RepositoryInterface $repo)
    {
        $this->id = (int)Request::getRequestInstance()->getSessionValue('userId') ?? null;
        $this->user = $this->getUserFromDB($this->id);
    }
    public function isAuth(): bool
    {
       return $this->user !== null; 
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    private function getUserFromDB(int $id): ?User
    {
        try{
            return $this->repo->find($id);
        } catch(DatabaseException $e){
            throw new DatabaseException($e->getMessage(),$e->getCode());
        }
    }
}