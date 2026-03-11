<?php

use App\Kernel\Connector\Interfaces\EntityInterface;
use App\Kernel\Files\FileUpload;

class EntityWithFileIn implements EntityInterface
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $firstname = null;
    private ?FileUpload $avatar= null;

    public function getid(): ?int
    {
        return $this->id;
    }

    public function setid(int $id): self
    {
        $this->id = $id;
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

    public function getAvatar(): ?FileUpload
    {
        return $this->avatar;
    }

    public function setAvatar(?FileUpload $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public static function getRepository(): ?string
    {
        return null;
    }
}