<?php

namespace App\Kernel\Interfaces;

use App\Security\User;

interface AuthenticationInterface
{
    public function isAuth(): bool;
    public function getUser(): ?User;
}