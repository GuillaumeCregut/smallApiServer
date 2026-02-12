<?php

namespace App\Security;

use App\Kernel\Connector\AbstractEntity;

class User extends AbstractEntity
{
    private array $roles = [];
    private ?string $name = null;
    private ?string $firstname = null;

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }
}