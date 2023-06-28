<?php

namespace SoftwarePunt\Instarecord\Tests\Pgsql;

use SoftwarePunt\Instarecord\Tests\Samples\User;

class PgsqlCoreTest extends PgsqlTestCase
{
    public function testAndPreparePgsqlConnection()
    {
        if ($skipReason = $this->getPgsqlUnavailableReason()) {
            $this->markTestSkipped($skipReason);
            return;
        }

        $this->expectNotToPerformAssertions();

        $connection = $this->createConnection();
        $connection->open();

        $this->assertTrue($connection->isOpen(), "pgsql connection should succeed");

        $connection->executeStatement('DROP TABLE IF EXISTS "users";');
        $connection->executeStatement('CREATE TABLE "users" (
  "id" SERIAL PRIMARY KEY,
  "user_name" varchar(45) NULL,
  "email_address" varchar(45) NULL,
  "enum_value" varchar(45) NULL,
  "join_date" timestamp NULL,
  "created_at" timestamp NULL,
  "modified_at" timestamp NULL
);');
    $connection->executeStatement('CREATE UNIQUE INDEX "user_email" ON "users" USING btree (
  "email_address" ASC NULLS LAST
);');
    }

    /**
     * @depends testAndPreparePgsqlConnection
     */
    public function testModelCrud()
    {
        $user = new User();
        $user->userName = "Bobby Tables";
        $this->assertTrue($user->save(), "User save should succeed");
        $this->assertGreaterThan(0, $user->id, "User creation should succeed; auto incremented ID should be set");
    }
}