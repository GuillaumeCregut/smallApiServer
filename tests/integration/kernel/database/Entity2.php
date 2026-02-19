<?php

use App\Kernel\Interfaces\Databases\EntityInterface;

class Entity2 implements EntityInterface
{
    private ?string $name = null;
    private ?string $firstname = null;
    private ?int $age = null;

    public function getid(): int
    {
        return 1;
    }

    public function setid(int $id): self
    {
        return $this;
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

    public function getRepository(): ?string
    {
        return null;
    }
}
