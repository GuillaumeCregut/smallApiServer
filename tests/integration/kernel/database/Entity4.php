<?php

use App\Kernel\Interfaces\Databases\EntityInterface;

class Entity4 implements EntityInterface
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $firstname = null;
    private ?int $age = null;
    private ?array $roles =[];

    public function getid(): int
    {
        return $this->id;
    }

    public function setid(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getRepository(): ?string
    {
        return null;
    }
    /**
     * Get the value of name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of firstname
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * Set the value of firstname
     */
    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get the value of age
     */
    public function getAge(): ?int
    {
        return $this->age;
    }

    /**
     * Set the value of age
     */
    public function setAge(?int $age): self
    {
        $this->age = $age;

        return $this;
    }

    /**
     * Get the value of role
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }


    /**
     * Set the value of role
     */
    public function setRole(?array $role): self
    {
        $this->roles = $role;

        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }


    
 
}
