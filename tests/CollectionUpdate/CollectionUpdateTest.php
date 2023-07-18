<?php

declare(strict_types=1);

namespace App\Tests\CollectionUpdate;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use DoctrineExamples\CollectionUpdate\EventListener\EventsResult;
use DoctrineExamples\CollectionUpdate\EventListener\EventsListener;
use DoctrineExamples\Entity\Comment;
use DoctrineExamples\Entity\User;
use DoctrineExamples\Tests\EntityManagerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests à faire :
 *   - les mêmes tests avec du lazy loading pour voir s'il charge les infos de la bdd ou les nôtres
 *
 * Problèmes :
 *   - si on a nos méthodes de gestion dans les entités, comment on gère les appels directs à Collection ?
 *
 * Informations :
 *   - cascade persist (côté User ou Comment) ne change rien aux tests
 */
class CollectionUpdateTest extends TestCase
{
    public function testAddComment(): void
    {
        $user = new User();
        $entityManager = EntityManagerFactory::getSingleton();
        $entityManager->persist($user);
        $entityManager->flush();

        $comment = new Comment($user);

        $eventsResult = $this->configureEvents(
            $user,
            $comment,
            $entityManager,
            [Events::prePersist, Events::postPersist, Events::preFlush, Events::onFlush, Events::postFlush]
        );

        $entityManager->persist($comment);

        # Nothing add $comment in $user::$comments
        static::assertFalse($user->getComments()->contains($comment));

        $entityManager->flush();

        # Nothing add $comment in $user::$comments
        static::assertFalse($user->getComments()->contains($comment));

        # $user::$comments is never updated with the new $comment
        static::assertFalse($eventsResult->prePersistComment);
        static::assertFalse($eventsResult->postPersistComment);
        static::assertFalse($eventsResult->preFlush);
        static::assertFalse($eventsResult->onFlush);
        static::assertFalse($eventsResult->postFlush);
    }

    public function testRemoveComment(): void
    {
        $user = new User();
        $comment = new Comment($user);
        $entityManager = EntityManagerFactory::getSingleton();
        $entityManager->persist($user);
        $entityManager->persist($comment);
        $entityManager->flush();

        # $user::$comments is never updated with the new $comment
        static::assertFalse($user->getComments()->contains($comment));
        $user->getComments()->add($comment);

        $eventsResult = $this->configureEvents(
            $user,
            $comment,
            $entityManager,
            [Events::preRemove, Events::postRemove, Events::preFlush, Events::onFlush, Events::postFlush]
        );

        $entityManager->remove($comment);
        # $comment exists in $user::$comments
        static::assertTrue($user->getComments()->contains($comment));

        $entityManager->flush();

        # $comment has been removed in $user::$comments by Doctrine
        static::assertFalse($user->getComments()->contains($comment));

        # $user::$comments is updated ($comment is removed) between onFlush (not updated) and postFlush (updated)
        static::assertTrue($eventsResult->preRemoveComment);
        static::assertTrue($eventsResult->postRemoveComment);
        static::assertTrue($eventsResult->preFlush);
        static::assertTrue($eventsResult->onFlush);
        static::assertFalse($eventsResult->postFlush);
    }

    public function testChangeCommentUser(): void
    {
        $user1 = new User();
        $user2 = new User();
        $comment = new Comment($user1);
        $entityManager = EntityManagerFactory::getSingleton();
        $entityManager->persist($user1);
        $entityManager->persist($user2);
        $entityManager->persist($comment);
        $entityManager->flush();

        # $user::$comments is never updated with the new $comment
        static::assertFalse($user1->getComments()->contains($comment));
        $user1->getComments()->add($comment);
        $comment->setUser($user2);

        $eventsResult = $this->configureEvents(
            $user1,
            $comment,
            $entityManager,
            [Events::preUpdate, Events::postUpdate, Events::preFlush, Events::onFlush, Events::postFlush]
        );

        static::assertTrue($user1->getComments()->contains($comment));
        static::assertFalse($user2->getComments()->contains($comment));

        $entityManager->flush();

        # /!\ $user1 should not contain $comment at this point, because $comment->getUser() === $user2!
        static::assertTrue($user1->getComments()->contains($comment));
        static::assertFalse($user2->getComments()->contains($comment));

        # Nothing remove $comment in $user1::$comments
        static::assertTrue($eventsResult->preUpdateComment);
        static::assertTrue($eventsResult->postUpdateComment);
        # preFlush is not called in this case
        // static::assertFalse($eventsResult->preFlush);
        static::assertTrue($eventsResult->onFlush);
        static::assertTrue($eventsResult->postFlush);
    }

    public function testClearComments(): void
    {
        $user = new User();
        $comment = new Comment($user);
        $entityManager = EntityManagerFactory::getSingleton();
        $entityManager->persist($user);
        $entityManager->persist($comment);
        $entityManager->flush();

        # $user::$comments is never updated with the new $comment
        static::assertFalse($user->getComments()->contains($comment));
        $user->getComments()->add($comment);

        $eventsResult = $this->configureEvents(
            $user,
            $comment,
            $entityManager,
            [Events::preRemove, Events::postRemove, Events::preFlush, Events::onFlush, Events::postFlush]
        );

        static::assertTrue($user->getComments()->contains($comment));
        $user->getComments()->clear();
        static::assertFalse($user->getComments()->contains($comment));

        $entityManager->flush();

        # $comment is not in $user::$comments, but in database, nothing has been deleted, so $comment is linked to $user
        static::assertFalse($user->getComments()->contains($comment));

        # No queries have been executed
        # preRemove is not called in this case
        // static::assertTrue($eventsResult->preRemoveComment);
        # postRemove is not called in this case
        // static::assertTrue($eventsResult->postRemoveComment);
        # preFlush is not called in this case
        // static::assertTrue($eventsResult->preFlush);
        # onFlush is not called in this case
        // static::assertTrue($eventsResult->onFlush);
        static::assertFalse($eventsResult->postFlush);
    }

    private function configureEvents(
        User $user,
        Comment $comment,
        EntityManagerInterface $entityManager,
        array $events
    ): EventsResult {
        $eventsResult = new EventsResult();
        $eventsListener = new EventsListener($user, $comment, $eventsResult);

        $eventManager = $entityManager->getEventManager();
        foreach ($events as $event) {
            $eventManager->addEventListener($event, $eventsListener);
        }

        return $eventsResult;
    }
}
