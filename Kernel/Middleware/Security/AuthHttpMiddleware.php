<?php

namespace App\Kernel\Middleware\Security;

use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Request;
use App\Kernel\Traits\GetUserAuthTrait;
use App\Security\User;

class AuthHttpMiddleware implements AuthenticationInterface
{

    use GetUserAuthTrait;

    private ?User $user=null;
    // Implementation for HTTP authentication middleware
    public function isAuth(): bool
    {
        $request = Request::getRequestInstance();
        $username = $request->getServer('PHP_AUTH_USER');
        $password = $request->getServer('PHP_AUTH_PW');

        throw new \Exception('Not implemented');
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}