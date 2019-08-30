<?php
declare(strict_types=1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use DoctrineExamples\Tests\EntityManagerFactory;

require_once __DIR__ . '/bootstrap.php';

return ConsoleRunner::createHelperSet(
    EntityManagerFactory::getSingleton()
);
