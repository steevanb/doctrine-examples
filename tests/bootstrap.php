<?php
declare(strict_types=1);

use DoctrineExamples\Tests\EntityManagerFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// the connection configuration
$dbParams = [
    'driver' => 'pdo_mysql',
    'user' => 'root',
    'password' => 'root',
    'dbname' => 'doctrine_examples',
    'host' => 'localhost'
];

EntityManagerFactory::createEntityManager($dbParams);
