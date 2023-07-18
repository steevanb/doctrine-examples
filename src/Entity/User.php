<?php

declare(strict_types=1);

namespace DoctrineExamples\Entity;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};

/**
 * User is the owning side of the manyToMany relation between User and Credentials.
 * Accessors code is exactly the same between User and Credential.
 * You can call EntityManager::flush($user): everything will be inserted, updated or deleted.
 * But not EntityManager::flush($credential): it will not remove deleted relation between User and Credential.
 * In this case, you have to call EntityManager::flush().
 */
class User
{
    private ?int $id;

    private Collection $credentials;

    private Collection $comments;

    public function __construct()
    {
        $this->id = null;
        $this->credentials = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCredentials(iterable $credentials): static
    {
        $this->clearCredentials();
        /** @var Credential $credential */
        foreach ($credentials as $credential) {
            $this->addCredential($credential);
        }

        return $this;
    }

    public function addCredential(Credential $credential): static
    {
        if ($this->credentials->contains($credential) === false) {
            $this->credentials->add($credential);
            $credential->addUser($this);
        }

        return $this;
    }

    /** @return Credential[]|Collection */
    public function getCredentials(): Collection
    {
        return $this->credentials;
    }

    public function removeCredential(Credential $credential): static
    {
        if ($this->credentials->contains($credential)) {
            $this->credentials->removeElement($credential);
            $credential->removeUser($this);
        }

        return $this;
    }

    public function clearCredentials(): static
    {
        foreach ($this->getCredentials() as $credential) {
            $this->removeCredential($credential);
        }
        $this->credentials->clear();

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }
}
