<?php

declare(strict_types=1);

namespace App\Tests\ManyToManyBidirectional;

use DoctrineExamples\{
    ManyToManyBidirectional\Entity\Credential,
    ManyToManyBidirectional\Entity\User,
    Tests\EntityManagerFactory
};
use PHPUnit\Framework\TestCase;

/**
 * Test all accessors on User to access Credential.
 * There is no mocks to be sure data are saved in database as expected.
 */
final class UserTest extends TestCase
{
    private static function assertUserObjectHash(User $user, int $credentialIndex): void
    {
        static::assertArrayHasKey($credentialIndex, $user->getCredentials());
        $users = $user->getCredentials()[$credentialIndex]->getUsers();

        static::assertCount(1, $users);
        static::assertArrayHasKey(0, $users);
        static::assertSame(
            spl_object_hash($user),
            spl_object_hash($users[0])
        );
    }

    public function testGetCredentials(): User
    {
        $user = (new User())
            ->addCredential($this->createCredential());

        static::assertCount(1, $user->getCredentials());
        static::assertArrayHasKey(0, $user->getCredentials());
        static::assertNull($user->getCredentials()[0]->getId());
        static::assertUserObjectHash($user, 0);

        return $user;
    }

    /** @depends testGetCredentials */
    public function testSaveUser(User $user): User
    {
        EntityManagerFactory::getSingleton()->persist($user);
        EntityManagerFactory::getSingleton()->flush($user);

        static::assertCount(1, $user->getCredentials());
        static::assertArrayHasKey(0, $user->getCredentials());
        static::assertIsInt($user->getCredentials()[0]->getId());
        static::assertUserObjectHash($user, 0);

        return $user;
    }

    /** @depends testSaveUser */
    public function testFreshUser(User $user): User
    {
        $savedUser = $this->getFreshUser($user->getId());
        static::assertArrayHasKey(0, $user->getCredentials());
        static::assertArrayHasKey(0, $savedUser->getCredentials());
        static::assertSame(
            $user->getCredentials()[0]->getId(),
            $savedUser->getCredentials()[0]->getId()
        );
        static::assertUserObjectHash($savedUser, 0);

        return $savedUser;
    }

    /** @depends testFreshUser */
    public function testSetCredentials(User $user): User
    {
        $credentials = $user->getCredentials();
        static::assertArrayHasKey(0, $credentials);
        $unlinkedCredential = $credentials[0];
        $unlinkedCredentialHash = spl_object_hash($unlinkedCredential);

        $user->setCredentials(
            [
                $this->createCredential(),
                $this->createCredential(),
                $this->createCredential(),
            ]
        );
        static::assertCount(3, $credentials);
        static::assertUserObjectHash($user, 0);
        static::assertNotSame($credentials[0], $unlinkedCredentialHash);
        static::assertUserObjectHash($user, 1);
        static::assertNotSame($credentials[1], $unlinkedCredentialHash);
        static::assertUserObjectHash($user, 2);
        static::assertNotSame($credentials[2], $unlinkedCredentialHash);

        EntityManagerFactory::getSingleton()->flush($user);

        $freshUser = $this->getFreshUser($user->getId());
        $savedCredentials = $freshUser->getCredentials();
        static::assertCount(3, $freshUser->getCredentials());

        for ($i = 0; $i <= 2; $i++) {
            static::assertArrayHasKey($i, $savedCredentials);
            static::assertIsInt($savedCredentials[$i]->getId());
            static::assertTrue($savedCredentials[$i]->getId() !== $unlinkedCredential->getId());
            static::assertUserObjectHash($freshUser, $i);
            static::assertNotSame($savedCredentials[$i], $unlinkedCredentialHash);
        }

        /** @var Credential $freshUnlinkedCredential */
        $freshUnlinkedCredential = EntityManagerFactory::getSingleton()
            ->getRepository(Credential::class)
            ->find($unlinkedCredential->getId());
        static::assertInstanceOf(Credential::class, $freshUnlinkedCredential);
        static::assertCount(0, $freshUnlinkedCredential->getUsers());

        return $freshUser;
    }

    /** @depends testSetCredentials */
    public function testRemoveCredential(User $user): User
    {
        static::assertCount(3, $user->getCredentials());
        static::assertArrayHasKey(0, $user->getCredentials());

        $user->removeCredential($user->getCredentials()[0]);

        static::assertCount(2, $user->getCredentials());

        static::assertArrayHasKey(1, $user->getCredentials());
        static::assertUserObjectHash($user, 1);

        static::assertArrayHasKey(2, $user->getCredentials());
        static::assertUserObjectHash($user, 2);

        return $user;
    }

    /** @depends testRemoveCredential */
    public function testFlushRemoveCredential(User $user): User
    {
        EntityManagerFactory::getSingleton()->flush($user);

        $savedUser = $this->getFreshUser($user->getId());

        static::assertCount(2, $savedUser->getCredentials());

        static::assertArrayHasKey(0, $savedUser->getCredentials());
        static::assertUserObjectHash($savedUser, 0);

        static::assertArrayHasKey(1, $savedUser->getCredentials());
        static::assertUserObjectHash($savedUser, 1);

        return $savedUser;
    }

    /** @depends testFlushRemoveCredential */
    public function testClearCredentials(User $user): User
    {
        $user->clearCredentials();

        static::assertCount(0, $user->getCredentials());

        return $user;
    }

    /** @depends testClearCredentials */
    public function testFlushClearCredentials(User $user): void
    {
        EntityManagerFactory::getSingleton()->flush($user);

        $savedUser = $this->getFreshUser($user->getId());
        static::assertCount(0, $savedUser->getCredentials());
    }

    private function createCredential(): Credential
    {
        return new Credential();
    }

    private function getFreshUser(int $id): User
    {
        EntityManagerFactory::getSingleton()->clear();

        /** @var User $return */
        $return = EntityManagerFactory::getSingleton()->getRepository(User::class)->find($id);
        if ($return instanceof User === false) {
            throw new \Exception('User "' . $id . '" cannot be found.');
        }

        return $return;
    }
}
