<?php

/**
 * Bootstrapper for PHPUnit environment.
 */

// Initialize the class autoloader
require_once __DIR__ . '/vendor/autoload.php';

define('TEST_DATABASE_NAME', 'testdb');
define('TEST_USER_NAME', 'root');
define('TEST_PASSWORD', 'dev123');

// Reset database
$pdo = new \PDO('mysql:host=localhost;dbname=' . TEST_DATABASE_NAME . ';charset=utf8', TEST_USER_NAME, TEST_PASSWORD);

$pdo->exec('DROP TABLE IF EXISTS users;');

$pdo->exec('CREATE TABLE `testdb`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(45) NULL,
  `email_address` VARCHAR(45) NULL,
  `join_date` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_name_UNIQUE` (`user_name` ASC));');

$pdo = null;