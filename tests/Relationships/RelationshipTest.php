<?php

namespace Relationships;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\TestAirline;
use SoftwarePunt\Instarecord\Tests\Samples\TestPlane;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

class RelationshipTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testRelationshipWrite()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Create a new airline
        $airline = new TestAirline();
        $airline->name = "Test Airline";
        $airline->iataCode = "TA";
        $airline->save();

        // Create some new plane
        $plane1 = new TestPlane();
        $plane1->name = "Test Plane 1";
        $plane1->registration = "TP-001";
        $plane1->airline = $airline;
        $plane1->save();

        $plane2 = new TestPlane();
        $plane2->name = "Test Plane 2";
        $plane2->registration = "TP-002";
        $plane2->airline = $airline;
        $plane2->save();

        // Assert that the plane's airline ID is set correctly
        $rows = TestPlane::query()
            ->queryAllRows();
        $this->assertCount(2, $rows, "Expected 2 planes to be inserted");
        foreach ($rows as $row) {
            $this->assertSame($airline->id, $row["airline_id"], "Expected airline ID to be set correctly on created rows");
        }
    }
}