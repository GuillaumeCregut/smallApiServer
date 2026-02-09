<?php

namespace App\Security;

use App\Kernel\Connector\AbstractEntity;

class User extends AbstractEntity
{
    private array $roles = [];
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRole(string $role): self
    {
        $this->roles[] = $role;
        return $this;
    }

    public function removeRole(string $role): self
    {
        $key = array_find_key($this->roles, function ($value) use ($role) {
            return $value === $role;
        });
        if(null !== $key){
            unset($this->roles[$key]);
        }
        return $this;
    }

    public function getToken(): string
    {
        //Todo : get user key from database
        return '';
    }
}