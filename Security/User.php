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
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }
}