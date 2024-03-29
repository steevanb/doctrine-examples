<?php

declare(strict_types=1);

namespace DoctrineExamples\Tests;

use Doctrine\ORM\{
    EntityManager,
    Tools\Setup
};

class EntityManagerFactory
{
    private static $entityManager;

    public static function createEntityManager(array $params): EntityManager
    {
        static::$entityManager = EntityManager::create(
            $params,
            Setup::createYAMLMetadataConfiguration(
                [__DIR__ . '/../config/doctrine/mapping'],
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
