<?php

/**
 * Bootstrapper for PHPUnit environment.
 */

// Initialize the class autoloader
require_once __DIR__ . '/vendor/autoload.php';

// PHPUnit config
$config = json_decode(file_get_contents(__DIR__ . '/phpunit-config.json'));

// Reset database
$pdo = new \PDO($config['db_connection'], $config['db_user'], $config['db_pass']);

$pdo->exec('DROP TABLE IF EXISTS users;');

$pdo->exec('CREATE TABLE `testdb`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(45) NULL,
  `email_address` VARCHAR(45) NULL,
  `join_date` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_name_UNIQUE` (`user_name` ASC));');

$pdo = null;