<?php

/**
 * Bootstrapper for PHPUnit environment.
 */

// Initialize the class autoloader
require_once __DIR__ . '/vendor/autoload.php';

// PHPUnit config
$config = json_decode(file_get_contents(__DIR__ . '/phpunit-config.json'), true);

// Reset database
$pdo = new \PDO($config['db_connection'], $config['db_user'], $config['db_pass']);

// Constants for backwards compat
define('TEST_USER_NAME', $config['db_user']);
define('TEST_PASSWORD', $config['db_pass']);
define('TEST_DATABASE_NAME', $config['db_name']);

$pdo->exec('DROP TABLE IF EXISTS users;');

$pdo->exec('CREATE TABLE `testdb`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(45) NULL,
  `email_address` VARCHAR(45) NULL,
  `join_date` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_name_UNIQUE` (`user_name` ASC));');

$pdo = null;