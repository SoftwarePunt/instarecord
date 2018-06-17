<?php

/**
 * Bootstrapper for PHPUnit environment.
 */

// Initialize the class autoloader
use Instasell\Instarecord\Config\DatabaseConfig;
use Instasell\Instarecord\Database\Connection;
use Instasell\Instarecord\DatabaseAdapter;

require_once __DIR__ . '/vendor/autoload.php';

// DB Config: Load from JSON, generate DSN, move on :-)
$testConfigRaw = json_decode(file_get_contents(__DIR__ . '/phpunit-config.json'), true);

// Constants for backwards compat
define('TEST_DATABASE_HOST', $testConfigRaw['db_host']);
define('TEST_DATABASE_PORT', $testConfigRaw['db_port']);
define('TEST_USER_NAME', $testConfigRaw['db_user']);
define('TEST_PASSWORD', $testConfigRaw['db_pass']);
define('TEST_DATABASE_NAME', $testConfigRaw['db_name']);

// Generate DSN
$dsnConfig = new DatabaseConfig();
$dsnConfig->adapter = DatabaseAdapter::MYSQL;
$dsnConfig->host = TEST_DATABASE_HOST;
$dsnConfig->port = TEST_DATABASE_PORT;
$dsnConfig->username = TEST_USER_NAME;
$dsnConfig->password = TEST_PASSWORD;
$dsnConfig->database = TEST_DATABASE_NAME;
$dsnConnection = new Connection($dsnConfig);

$dsnText = $dsnConnection->generateDsn();

$dsnConnection->close();
unset($dsnConnection);

// Local PDO instance to seed db
$pdo = new \PDO($dsnText, $testConfigRaw['db_user'], $testConfigRaw['db_pass']);

$pdo->exec('DROP TABLE IF EXISTS users;');
$pdo->exec('CREATE TABLE `testdb`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(45) NULL,
  `email_address` VARCHAR(45) NULL,
  `join_date` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_name_UNIQUE` (`user_name` ASC));');

unset($pdo);