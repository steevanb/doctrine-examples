<?php

declare(strict_types=1);

namespace DoctrineExamples\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class EntityManagerFactory
{
    private static $entityManager;

    public static function createEntityManager(array $params): EntityManager
    {
        static::$entityManager = EntityManager::create(
            $params,
            Setup::createYAMLMetadataConfiguration(
                [__DIR__ . '/../examples/ManyToManyBidirectional/Config'],
                true,
                __DIR__ . '/../var'
            )
        );

        return static::$entityManager;
    }

    public static function getSingleton(): EntityManager
    {
        return static::$entityManager;
    }
}
