<?php

namespace App\Security;

use App\Kernel\Interfaces\Databases\EntityInterface;

interface UserEntityInterface extends EntityInterface
{
     public function getRoles(): array;
     public function setRoles(array $roles): self;
     public function addRole(string $role): self;
     public function removeRole(string $role): self;
     public function getToken(): ?string;
     public function setToken(?string $token): self;
     public function getUsername(): ?string;
     public function setUsername(?string $username): self;
     public function getPassword(): ?string;
     public function setPassword(?string $password): self;
     public function setNewPassword(?string $password): self;
     public function getNewPassword(): ?string;

}