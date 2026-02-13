<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Middleware\Security;

use App\Security\User;
use App\Kernel\Request;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Interfaces\Databases\RepositoryInterface;
use App\Security\UserRepository;

class AuthHttpMiddleware implements AuthenticationInterface
{

    private ?User $user = null;
    /**
     * @param UserRepository $repo
     */
    public function __construct(private RepositoryInterface $repo)
    {
        $request = Request::getRequestInstance();
        $username = $request->getServer('PHP_AUTH_USER');
        $password = $request->getServer('PHP_AUTH_PW');
        $this->user = $this->getUserFromDb($username, $password);
    }

    public function isAuth(): bool
    {
        return $this->user !== null;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    private function getUserFromDb(string $user, string $password): ? User 
    {
        try {
            return $this->repo->findByUserNameCredentials($user, $password);
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode());
        }
        return null;
    }
}
