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
    public function testOneToOneRelationship()
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

        // Query a plane model and ensure the airline is loaded
        $plane1Reload = TestPlane::query()
            ->where("id = ?", $plane1->id)
            ->querySingleModel();
        $this->assertEquals($plane1->airline->id, $plane1Reload->airline->id,
            "Expected airline to be loaded and set automatically");

        // Test load with queryAllModels (batch optimizations)
        $allPlanesInDb = TestPlane::query()
            ->queryAllModels();
        $this->assertCount(2, $allPlanesInDb, "Expected 2 planes to be loaded");
        foreach ($allPlanesInDb as $plane) {
            $this->assertEquals($airline->id, $plane->airline->id, "Expected airline to be loaded and set automatically");
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testHasManyRelationship()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Create a new airline
        $airline = new TestAirline();
        $airline->name = "Test Airline";
        $airline->iataCode = "TA";
        $airline->save();

        // Check the initial "has many" relationship state
        $initialMany = $airline->planes();
        $this->assertSame("airline_id", $initialMany->foreignKeyColumn,
            "Foreign key name should be automatically derived if left null based on host table name");
        $this->assertEmpty($initialMany->all(),
            "Expected no planes to be loaded initially");

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

        // Retrieve the "has many" relationship
        $afterMany = $airline->planes();
        $this->assertSame($initialMany, $afterMany,
            "Expected the same cached relationship instance to be returned when called again");
        $this->assertEmpty($initialMany->all(),
            "Expected cached (empty) planes list to be returned if we ask for all()");
        $initialMany->reset();
        $this->assertCount(2, $initialMany->all(),
            "Expected 2 planes to be loaded after reset() and all()");

        // Has many relationship: partial load test
        $partialMany = $airline->planes();
        $partialMany->reset();
        $partialMany->addLoaded($plane2);
        $this->assertCount(2, $initialMany->all(),
            "Expected 2 planes to be loaded after reset(), addLoaded() and all()");
        $this->assertSame($plane2, $partialMany->all()[$plane2->id],
            "Expected original, cached relationship to be returned from addLoaded() call");

        // Has many relationship: result hook + cached fetch test
        $hookedMany = $airline->planes();
        $hookedMany->reset();
        $hookedPlane1 = $hookedMany->query()->where('id = ?', $plane1->id)->querySingleModel();
        $fetchPlane1 = $hookedMany->fetch($plane1->id);
        $this->assertSame($hookedPlane1, $fetchPlane1,
            "Expected cached fetch() to return the same model instance as hooked query()->querySingleModel()");
    }
}