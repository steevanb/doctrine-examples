<?php
declare(strict_types=1);

use DoctrineExamples\Tests\EntityManagerFactory;

require_once __DIR__ . '/vendor/autoload.php';

EntityManagerFactory::createEntityManager(
    [
        'driver' => 'pdo_mysql',
        'user' => 'root',
        'password' => 'root',
        'dbname' => 'doctrine_examples',
        'host' => 'mysql'
    ]
);
