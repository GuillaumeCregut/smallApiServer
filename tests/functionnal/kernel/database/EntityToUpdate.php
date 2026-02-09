<?php

use App\Kernel\Connector\Attributes\NotStored;
use App\Kernel\Connector\Attributes\Nullable;
use App\Kernel\Interfaces\Databases\EntityInterface;

class EntityToUpdate implements EntityInterface
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $firstName = null;
    #[Nullable]
    private ?int $age = null;
    #[NotStored]
    private ?string $notStored = null;

    public function getid(): ?int
    {
        return $this->id;
    }

    public function setid(int $id): self
    {
        $this->id =$id;
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
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Set the value of firstname
     */
    public function setFirstName(?string $firstname): self
    {
        $this->firstName = $firstname;

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
