<?php

namespace App\Security;

use App\Services\Connector;

class User
{
    private int $id;

    public function __construct(private Connector $connector)
    {
    }

    public function getRole(): array
    {
        //Todo : get user role from database
        return ['ROLE_USER'];
    }

    public function getToken(): string
    {
        //Todo : get user key from database
        return '';
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function fetchFromDb(): void
    {
        //Todo : fetch user data from database using $this->id
    }
}