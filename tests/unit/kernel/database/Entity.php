<?php

use App\Kernel\Connector\Interfaces\EntityInterface;

class Entity implements EntityInterface
{
    private ?string $name = null;
    private ?string $firstname = null;
    private ?int $age = null;
    private int $id = 0;

    public function getId(): int
    {
        return 1;
    }
    public function getRepository(): ?string
    {
        return null;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
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
}
