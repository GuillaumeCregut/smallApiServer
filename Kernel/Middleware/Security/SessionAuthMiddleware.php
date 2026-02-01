<?php

namespace App\Kernel\Middleware\Security;

use App\Interfaces\ConnectorInterface;
use App\Security\User;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\Request;
use App\Kernel\Traits\GetUserAuthTrait;

class SessionAuthMiddleware implements AuthenticationInterface
{
    private ?User $user = null;

    use GetUserAuthTrait;

    public function __construct(private ConnectorInterface $connector)
    {
        // Initialization if needed
    }
    public function isAuth(): bool
    {
        $id = (int)Request::getRequestInstance()->getSessionValue('user_id') ?? null;
        $this->user = $this->getUserFromDB($id);
       return $this->user !== null; 
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}