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
  "id" int8 NOT NULL,
  "user_name" varchar(45) NULL,
  "email_address" varchar(45) NULL,
  "enum_value" varchar(45) NULL,
  "join_date" timestamp NULL,
  "created_at" timestamp NULL,
  "modified_at" timestamp NULL,
  CONSTRAINT "users_pkey" PRIMARY KEY ("id")
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
        $user->save();
    }
}