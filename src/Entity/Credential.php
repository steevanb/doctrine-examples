<?php

declare(strict_types=1);

namespace DoctrineExamples\Entity;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};

class Credential
{
    private ?int $id;

    private Collection $users;

    public function __construct()
    {
        $this->id = null;
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUsers(iterable $users): static
    {
        $this->clearUsers();
        /** @var User $user */
        foreach ($users as $user) {
            $this->addUser($user);
        }

        return $this;
    }

    public function addUser(User $user): static
    {
        if ($this->users->contains($user) === false) {
            $this->users->add($user);
            $user->addCredential($this);
        }

        return $this;
    }

    /** @return User[]|Collection */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeCredential($this);
        }

        return $this;
    }

    public function clearUsers(): static
    {
        foreach ($this->getUsers() as $user) {
            $this->removeUser($user);
        }
        $this->users->clear();

        return $this;
    }
}
