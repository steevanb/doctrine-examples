<?php
declare(strict_types=1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once __DIR__ . '/tests/bootstrap.php';

return ConsoleRunner::createHelperSet($GLOBALS['entityManager']);
