<?php

declare(strict_types=1);

namespace DoctrineExamples\ManyToManyBidirectional\Entity;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};

class Credential
{
    /** @var ?int */
    private $id;

    /** @var Collection */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUsers(iterable $users): self
    {
        $this->clearUsers();
        /** @var User $user */
        foreach ($users as $user) {
            $this->addUser($user);
        }

        return $this;
    }

    public function addUser(User $user): self
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

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeCredential($this);
        }

        return $this;
    }

    public function clearUsers(): self
    {
        foreach ($this->getUsers() as $user) {
            $this->removeUser($user);
        }
        $this->users->clear();

        return $this;
    }
}
