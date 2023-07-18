<?php

declare(strict_types=1);

namespace App\Tests\ManyToManyBidirectional;

use DoctrineExamples\{
    Entity\Credential,
    Entity\User,
    Tests\EntityManagerFactory};
use PHPUnit\Framework\TestCase;

/**
 * Test all accessors on Credential to access User.
 * There is no mocks to be sure data are saved in database as expected.
 */
final class CredentialTest extends TestCase
{
    private static function assertCredentialObjectHash(Credential $credential, int $userIndex): void
    {
        static::assertArrayHasKey($userIndex, $credential->getUsers());
        $credentials = $credential->getUsers()[$userIndex]->getCredentials();

        static::assertCount(1, $credentials);
        static::assertArrayHasKey(0, $credentials);
        static::assertSame(
            spl_object_hash($credential),
            spl_object_hash($credentials[0])
        );
    }

    public function testGetUsers(): Credential
    {
        $credential = (new Credential())
            ->addUser($this->createUser());

        static::assertCount(1, $credential->getUsers());
        static::assertArrayHasKey(0, $credential->getUsers());
        static::assertNull($credential->getUsers()[0]->getId());
        static::assertCredentialObjectHash($credential, 0);

        return $credential;
    }

    /** @depends testGetUsers */
    public function testSaveCredential(Credential $credential): Credential
    {
        EntityManagerFactory::getSingleton()->persist($credential);
        EntityManagerFactory::getSingleton()->flush($credential);

        static::assertCount(1, $credential->getUsers());
        static::assertArrayHasKey(0, $credential->getUsers());
        static::assertIsInt($credential->getUsers()[0]->getId());
        static::assertCredentialObjectHash($credential, 0);

        return $credential;
    }

    /** @depends testSaveCredential */
    public function testFreshCredential(Credential $credential): Credential
    {
        $savedCredential = $this->getFreshCredential($credential->getId());
        static::assertArrayHasKey(0, $credential->getUsers());
        static::assertArrayHasKey(0, $savedCredential->getUsers());
        static::assertSame(
            $credential->getUsers()[0]->getId(),
            $savedCredential->getUsers()[0]->getId()
        );
        static::assertCredentialObjectHash($savedCredential, 0);

        return $savedCredential;
    }

    /** @depends testFreshCredential */
    public function testSetUsers(Credential $credential): Credential
    {
        $users = $credential->getUsers();
        static::assertArrayHasKey(0, $users);
        $unlinkedUser = $users[0];
        $unlinkedUserHash = spl_object_hash($unlinkedUser);

        $credential->setUsers(
            [
                $this->createUser(),
                $this->createUser(),
                $this->createUser(),
            ]
        );
        static::assertCount(3, $users);
        static::assertCredentialObjectHash($credential, 0);
        static::assertNotSame($users[0], $unlinkedUserHash);
        static::assertCredentialObjectHash($credential, 1);
        static::assertNotSame($users[1], $unlinkedUserHash);
        static::assertCredentialObjectHash($credential, 2);
        static::assertNotSame($users[2], $unlinkedUserHash);

        // We can't flush on the inverse side of a manyToMany, because it will not save deleted relations
        // So we have to flush everything, not only $credential
        EntityManagerFactory::getSingleton()->flush();

        $savedCredential = $this->getFreshCredential($credential->getId());
        $savedUsers = $savedCredential->getUsers();

        static::assertCount(3, $savedUsers);

        for ($i = 0; $i <= 2; $i++) {
            static::assertArrayHasKey($i, $savedUsers);
            static::assertIsInt($savedUsers[$i]->getId());
            static::assertTrue($savedUsers[$i]->getId() !== $unlinkedUser->getId());
            static::assertCredentialObjectHash($savedCredential, $i);
        }

        /** @var User $freshUnlinkedUser */
        $freshUnlinkedUser = EntityManagerFactory::getSingleton()
            ->getRepository(User::class)
            ->find($unlinkedUser->getId());
        static::assertInstanceOf(User::class, $freshUnlinkedUser);
        static::assertCount(0, $freshUnlinkedUser->getCredentials());

        return $savedCredential;
    }

    /** @depends testSetUsers */
    public function testRemoveUser(Credential $credential): Credential
    {
        static::assertCount(3, $credential->getUSers());
        static::assertArrayHasKey(0, $credential->getUSers());

        $credential->removeUser($credential->getUSers()[0]);

        static::assertCount(2, $credential->getUSers());

        static::assertArrayHasKey(1, $credential->getUSers());
        static::assertCredentialObjectHash($credential, 1);

        static::assertArrayHasKey(2, $credential->getUSers());
        static::assertCredentialObjectHash($credential, 2);

        return $credential;
    }

    /** @depends testRemoveUser */
    public function testFlushRemoveUser(Credential $credential): Credential
    {
        // We can't flush on the inverse side of a manyToMany, because it will not save deleted relations
        // So we have to flush everything, not only $credential
        EntityManagerFactory::getSingleton()->flush();

        $freshCredential = $this->getFreshCredential($credential->getId());

        static::assertCount(2, $freshCredential->getUsers());

        static::assertArrayHasKey(0, $freshCredential->getUsers());
        static::assertCredentialObjectHash($freshCredential, 0);

        static::assertArrayHasKey(1, $freshCredential->getUsers());
        static::assertCredentialObjectHash($freshCredential, 1);

        return $freshCredential;
    }

    /** @depends testFlushRemoveUser */
    public function testClearUsers(Credential $credential): Credential
    {
        $credential->clearUsers();

        static::assertCount(0, $credential->getUsers());

        return $credential;
    }

    /** @depends testClearUsers */
    public function testFlushClearUsers(Credential $credential): void
    {
        // We can't flush on the inverse side of a manyToMany, because it will not save deleted relations
        // So we have to flush everything, not only $credential
        EntityManagerFactory::getSingleton()->flush();

        $freshCredential = $this->getFreshCredential($credential->getId());
        static::assertCount(0, $freshCredential->getUsers());
    }

    /**
     * This test show you should NEVER call EntityManager::flush($entity) on an inverse side
     * Because it will NOT delete rows in join table, although your objects seems to have the right data
     */
    public function testFlushRemovedUser(): void
    {
        $user = $this->createUser();
        $credential = (new Credential())
            ->addUser($user);

        EntityManagerFactory::getSingleton()->persist($credential);
        EntityManagerFactory::getSingleton()->flush($credential);

        $credential->removeUser($user);

        // Calling flush() on $credential will not delete the row in join table
        // Because Credential::$users is the inverse side
        EntityManagerFactory::getSingleton()->flush($credential);

        // You can expect to have 0 users into $credential here, but that's not true
        static::assertCount(1, $this->getFreshCredential($credential->getId())->getUsers());
    }

    private function createUser(): User
    {
        return new User();
    }

    private function getFreshCredential(int $id): Credential
    {
        EntityManagerFactory::getSingleton()->clear();

        /** @var \DoctrineExamples\Entity\Credential $return */
        $return = EntityManagerFactory::getSingleton()->getRepository(Credential::class)->find($id);
        if ($return instanceof Credential === false) {
            throw new \Exception('Credential "' . $id . '" cannot be found.');
        }

        return $return;
    }
}
