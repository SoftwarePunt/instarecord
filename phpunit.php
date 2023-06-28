<?php

/**
 * Bootstrapper for PHPUnit environment.
 */

// Initialize the class autoloader
use SoftwarePunt\Instarecord\Config\DatabaseConfig;
use SoftwarePunt\Instarecord\Database\Connection;
use SoftwarePunt\Instarecord\DatabaseAdapter;

require_once __DIR__ . '/vendor/autoload.php';

// DB Config: Load from JSON, generate DSN, move on :-)
$testConfigRaw = json_decode(file_get_contents(__DIR__ . '/phpunit-config.json'), true);

if (!$testConfigRaw) {
    echo "ERROR: Config file (phpunit-config.json) not found, can't run tests" . PHP_EOL;
    exit(1);
}

// Environment variables (GitHub actions)
$ENV_DB_PORT = intval(getenv('DB_PORT'));

if ($ENV_DB_PORT > 0) {
    $testConfigRaw['db_port'] = $ENV_DB_PORT;
}

// Constants for backwards compat
define('TEST_UNIX_SOCKET', $testConfigRaw['db_unix_socket'] ?? null);
define('TEST_DATABASE_HOST', $testConfigRaw['db_host'] ?? null);
define('TEST_DATABASE_PORT', $testConfigRaw['db_port'] ?? 3306);
define('TEST_USER_NAME', $testConfigRaw['db_user'] ?? 'root');
define('TEST_PASSWORD', $testConfigRaw['db_pass'] ?? '');
define('TEST_DATABASE_NAME', $testConfigRaw['db_name'] ?? 'test_db');

// Generate DSN (by using a temporary Connection instance)
$dsnConfig = new DatabaseConfig();
$dsnConfig->adapter = DatabaseAdapter::MySql;
if (TEST_UNIX_SOCKET) {
    $dsnConfig->unix_socket = TEST_UNIX_SOCKET;
    $dsnConfig->host = null;
    $dsnConfig->port = null;
} else if (TEST_DATABASE_HOST) {
    $dsnConfig->unix_socket = null;
    $dsnConfig->host = TEST_DATABASE_HOST;
    $dsnConfig->port = TEST_DATABASE_PORT;
}
$dsnConfig->username = TEST_USER_NAME;
$dsnConfig->password = TEST_PASSWORD;
$dsnConfig->database = TEST_DATABASE_NAME;

$dsnConnection = new Connection($dsnConfig);
$dsnText = $dsnConnection->generateDsn();
$dsnConnection->close();
unset($dsnConnection);

// Local PDO instance to seed db
$pdo = new \PDO($dsnText, $testConfigRaw['db_user'], $testConfigRaw['db_pass']);

$pdo->exec('DROP TABLE IF EXISTS `'  . $dsnConfig->database . '`.`users`;');
$pdo->exec('CREATE TABLE `'  . $dsnConfig->database . '`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(45) NULL,
  `email_address` VARCHAR(45) NULL,
  `enum_value` VARCHAR(45) NULL,
  `join_date` DATETIME NULL,
  `created_at` DATETIME NULL,
  `modified_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_name_UNIQUE` (`user_name` ASC));');

unset($pdo);