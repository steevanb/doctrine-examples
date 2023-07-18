<?php

declare(strict_types=1);

namespace DoctrineExamples\CollectionUpdate\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use DoctrineExamples\Entity\Comment;
use DoctrineExamples\Entity\User;

class EventsListener
{
    public function __construct(
        private readonly User $user,
        private readonly Comment $comment,
        private readonly EventsResult $eventsResult
    ) {
    }

    public function preRemove(PreRemoveEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof Comment) {
            $this->eventsResult->preRemoveComment = $this->user->getComments()->contains($entity);
        }
    }

    public function postRemove(PostRemoveEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof Comment) {
            $this->eventsResult->postRemoveComment = $this->user->getComments()->contains($entity);
        }
    }

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof Comment) {
            $this->eventsResult->prePersistComment = $this->user->getComments()->contains($entity);
        } elseif ($entity instanceof User) {
            $this->eventsResult->prePersistUser = $this->user->getComments()->contains($entity);
        }
    }

    public function postPersist(PostPersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof Comment) {
            $this->eventsResult->postPersistComment = $this->user->getComments()->contains($entity);
        } elseif ($entity instanceof User) {
            $this->eventsResult->postPersistUser = $this->user->getComments()->contains($entity);
        }
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof Comment) {
            $this->eventsResult->preUpdateComment = $this->user->getComments()->contains($entity);
        } elseif ($entity instanceof User) {
            $this->eventsResult->preUpdateUser = $this->user->getComments()->contains($entity);
        }
    }

    public function postUpdate(PostUpdateEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();

        if ($entity instanceof Comment) {
            $this->eventsResult->postUpdateComment = $this->user->getComments()->contains($entity);
        } elseif ($entity instanceof User) {
            $this->eventsResult->postUpdateUser = $this->user->getComments()->contains($entity);
        }
    }

    public function postLoad(): void
    {

    }

    public function preFlush(PreFlushEventArgs $eventArgs): void
    {
        foreach ($this->getEntities($eventArgs->getObjectManager()->getUnitOfWork()) as $entity) {
            if ($entity instanceof Comment) {
                $this->eventsResult->preFlush = $this->user->getComments()->contains($entity);
            } elseif ($entity instanceof User) {
                $this->eventsResult->preFlush = $this->user->getComments()->contains($entity);
            }
        }
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        foreach ($this->getEntities($eventArgs->getObjectManager()->getUnitOfWork()) as $entity) {
            if ($entity instanceof Comment) {
                $this->eventsResult->onFlush = $this->user->getComments()->contains($entity);
            } elseif ($entity instanceof User) {
                $this->eventsResult->onFlush = $this->user->getComments()->contains($entity);
            }
        }
    }

    public function postFlush(): void
    {
        $this->eventsResult->postFlush = $this->user->getComments()->contains($this->comment);
    }

    private function getEntities(UnitOfWork $unitOfWork): array
    {
        return array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $unitOfWork->getScheduledEntityUpdates(),
            $unitOfWork->getScheduledEntityDeletions()
        );
    }
}
