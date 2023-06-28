<?php

namespace SoftwarePunt\Instarecord\Tests\Pgsql;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Adapters\PostgreSqlAdapter;
use SoftwarePunt\Instarecord\Config\DatabaseConfig;
use SoftwarePunt\Instarecord\Database\Connection;
use SoftwarePunt\Instarecord\Instarecord;

abstract class PgsqlTestCase extends TestCase
{
    public function getPgsqlUnavailableReason(): ?string
    {
        if (!extension_loaded('pdo_pgsql'))
            return "Skipping pgsql tests: pdo_pgsql extension is missing";

        $configPath = $this->getPgsqlConfigPath();
        if (!file_exists($configPath))
            return "Skipping pgsql tests: config file does not exist: {$configPath}";

        // ok
        return null;
    }

    public function getPgsqlConfigPath(): string
    {
        return realpath(__DIR__ . '/../../') . '/phpunit-config-pgsql.json';
    }

    public function createConfig(): DatabaseConfig
    {
        $testConfigRaw = json_decode(file_get_contents($this->getPgsqlConfigPath()), true);

        if ($testConfigRaw === null)
            throw new \RuntimeException("Config file could not be read: {$this->getPgsqlConfigPath()}");

        $dbConfig = new DatabaseConfig();
        $dbConfig->adapter = PostgreSqlAdapter::class;
        $dbConfig->unix_socket = $testConfigRaw['db_unix_socket'] ?? null;
        if (!$dbConfig->unix_socket) {
            $dbConfig->host = $testConfigRaw['db_host'] ?? null;
            $dbConfig->port = $testConfigRaw['db_port'] ?? 3306;
        }
        $dbConfig->username = $testConfigRaw['db_user'] ?? 'root';
        $dbConfig->password = $testConfigRaw['db_pass'] ?? '';
        $dbConfig->database = $testConfigRaw['db_name'] ?? 'test_db';

        return $dbConfig;
    }

    public function createConnection(): Connection
    {
        $instarecord = new Instarecord('pgsql');
        $instarecord->getOrSetConfig($this->createConfig());
        return $instarecord->getConnection();
    }
}