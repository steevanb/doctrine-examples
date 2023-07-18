<?php

declare(strict_types=1);

namespace DoctrineExamples\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\HasLifecycleCallbacks]
class Comment
{
    private ?int $id;

    public function __construct(private User $user)
    {
        $this->id = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
