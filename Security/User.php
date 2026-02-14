<?php

namespace App\Security;

use App\Kernel\Connector\AbstractEntity;

class User extends AbstractEntity
{
    private array $roles = [];
    private ?string $name = null;
    private ?string $firstname = null;
    private ?string $username = null;
    private ?string $password = null;
    private ?string $token = null;
    
    final public function getRoles(): array
    {
        return $this->roles;
    }

    final public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    final public function setRole(string $role): self
    {
        $this->roles[] = $role;
        return $this;
    }

    final public function removeRole(string $role): self
    {
        $key = array_find_key($this->roles, function ($value) use ($role) {
            return $value === $role;
        });
        if(null !== $key){
            unset($this->roles[$key]);
        }
        return $this;
    }

    final public function getToken(): string
    {
        return $this->token;
    }

    final public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    final public function getName(): ?string
    {
        return $this->name;
    }

    final public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    final public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    final public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    final public function getUsername(): ?string
    {
        return $this->username;
    }

    final public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    final public function getPassword(): ?string
    {
        return $this->password;
    }

    final public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }
}