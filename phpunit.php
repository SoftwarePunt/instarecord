<?php

/**
 * Bootstrapper for PHPUnit environment.
 */

// Initialize the class autoloader
require_once __DIR__ . '/vendor/autoload.php';

define('TEST_DATABASE_NAME', 'testdb');
define('TEST_USER_NAME', 'root');
define('TEST_PASSWORD', 'dev123');