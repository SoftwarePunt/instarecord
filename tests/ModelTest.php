<?php

namespace SoftwarePunt\Instarecord\Tests;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Database\Column;
use SoftwarePunt\Instarecord\Instarecord;
use SoftwarePunt\Instarecord\Tests\Samples\TestDummySerializableType;
use Softwarepunt\Instarecord\Tests\Samples\TestEnum;
use SoftwarePunt\Instarecord\Tests\Samples\TestUserAuto;
use SoftwarePunt\Instarecord\Tests\Samples\TestUserWithSerialized;
use SoftwarePunt\Instarecord\Tests\Samples\TestUser;
use SoftwarePunt\Instarecord\Tests\Testing\TestDatabaseConfig;

class ModelTest extends TestCase
{
    public function testConstructWithDefaults()
    {
        $user = new TestUser([
            'id' => 123,
            'userName' => 'Henk'
        ]);
        
        $this->assertEquals(123, $user->id);
        $this->assertEquals('Henk', $user->userName);
    }
    
    public function testGetProperties()
    {
        $values = [
            'id' => 123,
            'userName' => 'Henk'
        ];
        
        $user = new TestUser($values);
        
        $this->assertEquals($values['id'], $user->getPropertyValues()['id']);
        $this->assertEquals($values['userName'], $user->getPropertyValues()['userName']);
    }
    
    public function testGetSetDirtyProperties()
    {
        $values = [
            'id' => 123,
            'userName' => 'Henk'
        ];

        $user = new TestUser($values);

        // Initial state; should be clean
        $this->assertEmpty($user->getDirtyProperties());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->getPropertyIsDirty("userName"));

        // Modify individual property; should become dirty
        $user->userName = 'Jan';
        $this->assertEquals(['userName' => 'Jan'], $user->getDirtyProperties());
        $this->assertTrue($user->getPropertyIsDirty("userName"));
        $this->assertFalse($user->getPropertyIsDirty("id"));
        $this->assertTrue($user->isDirty());

        // Manually flag as clean; should become clean
        $user->setPropertyIsDirty("userName", false);
        $this->assertEmpty($user->getDirtyProperties());
        $this->assertFalse($user->getPropertyIsDirty("userName"));

        // Manually flag as clean; should become dirty again
        $user->setPropertyIsDirty("userName", true);
        $this->assertEquals(['userName' => 'Jan'], $user->getDirtyProperties());
        $this->assertTrue($user->getPropertyIsDirty("userName"));
    }
    
    public function testGetDirtyColumns()
    {
        $values = [
            'id' => 123,
            'user_name' => 'Henk'
        ];

        $user = new TestUser($values);

        $this->assertEmpty($user->getDirtyColumns(), 'Dirty columns should initially be empty');

        $user->userName = 'Jan';

        $this->assertEquals(['user_name' => 'Jan'], $user->getDirtyColumns(), 'Changing a value should affect dirty columns');
    }
    
    public function testMarkAllDirty()
    {
        $values = [
            'id' => 123,
            'userName' => 'Henk'
        ];

        $user = new TestUser($values);

        $this->assertEmpty($user->getDirtyProperties());
        
        $user->markAllPropertiesDirty();
        
        $this->assertNotEmpty($user->getDirtyProperties());
    }

    /**
     * @depends testMarkAllDirty
     */
    public function testMarkAllClean()
    {
        $values = [
            'id' => 123,
            'userName' => 'Henk'
        ];

        $user = new TestUser($values);

        $user->markAllPropertiesDirty();

        $this->assertNotEmpty($user->getDirtyProperties());
        
        $user->markAllPropertiesClean();

        $this->assertEmpty($user->getDirtyProperties());
    }
    
    public function testGetPropertyNames()
    {
        $sampleUserModel = new TestUser();
        $propertyList = $sampleUserModel->getPropertyNames();
        
        $this->assertNotEmpty($propertyList, 'A property list should not be empty');
        $this->assertContains('id', $propertyList, 'The "id" property should be returned');
        $this->assertContains('userName', $propertyList, 'The "user_name" property should be returned');
        $this->assertNotContains('secretNotWritable', $propertyList, 'Private properties should not be included in property list');
    }
    
    public function testGetColumnNames()
    {
        $sampleUserModel = new TestUser();
        $propertyList = $sampleUserModel->getColumnNames();

        $this->assertNotEmpty($propertyList, 'A property list should not be empty');
        $this->assertContains('id', $propertyList, 'The "id" property should be returned');
        $this->assertContains('user_name', $propertyList, 'The "user_name" property should be returned and translated');
        $this->assertNotContains('secretNotWritable', $propertyList, 'Private properties should not be included in property list');
    }

    public function testGetColumnNameForPropertyName()
    {
        $sampleUserModel = new TestUser();
        $this->assertEquals('id', $sampleUserModel->getColumnNameForPropertyName('id'));
        $this->assertEquals('user_name', $sampleUserModel->getColumnNameForPropertyName('userName'));
    }

    public function testGetPropertyNameForColumnName()
    {
        $sampleUserModel = new TestUser();
        $this->assertEquals('id', $sampleUserModel->getPropertyNameForColumnName('id'));
        $this->assertEquals('userName', $sampleUserModel->getPropertyNameForColumnName('user_name'));
    }
    
    public function testGetTableName()
    {
        $sampleUserModel = new TestUser(); // has getTableName() implemented
        $this->assertEquals('users', $sampleUserModel->getTableName());
    }

    public function testGetTableNameFromAttribute()
    {
        $sampleUserModel = new TestUserAuto(); // has #[TableName] attribute
        $this->assertEquals('users', $sampleUserModel->getTableName());
    }

    public function testSetColumnValues()
    {
        $user = new TestUser();
        $user->setColumnValues([
            'id' => 5,
            'user_name' => 'Bob'
        ]);

        $this->assertEquals('Bob', $user->userName);
        $this->assertEquals(5, $user->id);
        $this->assertFalse(isset($user->joinDate));
    }

    public function testGetAndSetColumnValues()
    {
        $user = new TestUser();

        $testSet = [
            'id' => 5,
            'user_name' => 'Bob'
        ];

        $user->setColumnValues($testSet);

        $actualFromGet = $user->getColumnValues();

        $this->assertEquals($testSet['id'], $actualFromGet['id']);
        $this->assertEquals($testSet['user_name'], $actualFromGet['user_name']);
    }

    public function testGetPropertyValuesWithColumnNames()
    {
        $user = new TestUser();

        $testDate = new \DateTime();
        $testDate->setDate(1978, 1, 2);
        $testDate->setTime(3, 4, 5);

        $testSet = [
            'id' => 5,
            'user_name' => "Bob",
            'join_date' => $testDate->format('Y-m-d H:i:s')
        ];

        $user->setColumnValues($testSet);

        $actualFromGet = $user->getPropertyValuesWithColumnNames();

        $this->assertSame($testSet['id'], $actualFromGet['id']);
        $this->assertSame($testSet['user_name'], $actualFromGet['user_name']);
        $this->assertEquals($testDate, $actualFromGet['join_date']);
    }

    /**
     * @runInSeparateProcess 
     */
    public function testCreateSimple()
    {
        Instarecord::config(new TestDatabaseConfig());

        $newUser = new TestUser();
        $newUser->userName = "my-test-user-one";
        
        $this->assertTrue($newUser->create(), 'Creating a new record should return TRUE');
        $this->assertNotEmpty($newUser->id, 'Creating a new record should update its primary key');
        $this->assertEmpty($newUser->getDirtyProperties(), 'After creating an item, all properties should be clean');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateWithoutAutoIncrement()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Determine what the next auto increment number would be (max+1)
        $nextUserId = intval(TestUser::query()
            ->select('id')
            ->orderBy('id DESC')
            ->limit(1)
            ->querySingleValue()) + 1;

        // Create user without auto increment
        $newUser = new TestUser();
        $newUser->id = $nextUserId;
        $newUser->setUseAutoIncrement(false);
        $newUser->userName = "non-auto-incremented";

        $this->assertTrue($newUser->create(), "Creating valid record without auto increment should return TRUE");
        $this->assertSame($nextUserId, $newUser->id, "Creating record without auto increment should leave primary key unchanged");

        // Test update
        $newUser->userName = "updated-non-auto";

        $this->assertTrue($newUser->save(), "Updating non-auto incremented record should succeed");
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateViaSave()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        $newUser = new TestUser();
        $newUser->userName = "my-test-user-two";

        $this->assertTrue($newUser->save(), 'Creating a new record should return TRUE (via save)');
        $this->assertNotEmpty($newUser->id, 'Creating a new record should update its primary key (via save)');
        $this->assertEmpty($newUser->getDirtyProperties(), 'After saving an item, all properties should be clean');
    }

    public function testReload()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Create new user
        $newUser = new TestUser();
        $newUser->userName = "reload-user";
        $newUser->enumValue = TestEnum::One;
        $this->assertTrue($newUser->save(), "New user save should succeed");
        $this->assertNotEmpty($newUser->id, "New user save should set PK value");

        // Update user by query
        TestUser::query()
            ->update()
            ->set('`enum_value` = ?', TestEnum::Two->value)
            ->where('id = ?', $newUser->id)
            ->execute();

        // Reload user
        $this->assertTrue($newUser->reload(), "Reload should succeed");
        $this->assertEquals(TestEnum::Two, $newUser->enumValue, "Reloaded data should be applied");

        // Delete & reload user
        $newUser->delete();
        $this->assertFalse($newUser->reload(), "Reload should fail for deleted record");
    }

    /**
     * @depends testCreateViaSave
     * @runInSeparateProcess 
     */
    public function testUpdateCreatedRecordViaSave()
    {
        Instarecord::config(new TestDatabaseConfig());

        // 1. Insert user
        $newUser = new TestUser();
        $newUser->userName = "my-test-user-three";
        $newUser->save();
        
        $idAfterInsertion = $newUser->id + 0;

        // 2. Modify it
        $newUser->userName = 'blah-blah-blah';
        
        $this->assertArrayHasKey('userName', $newUser->getDirtyProperties(), 'userName should now be flagged as a dirty property');
        $this->assertTrue($newUser->save(), 'Update should return true');
        
        // 3. Ensure it was updated in the database
        $this->assertEquals($idAfterInsertion, $newUser->id, 'Updating should never result in a modified primary key');
    }

    /**
     * @runInSeparateProcess
     */
    public function testUpsert()
    {
        Instarecord::config(new TestDatabaseConfig());

        // NB: "userName" has a unique index

        // 1. Upsert initial
        $user1 = new TestUser();
        $user1->userName = "mr-upsert";
        $user1->enumValue = TestEnum::One;

        $this->assertTrue($user1->upsert(), "upsert() should succeed on create");
        $this->assertNotNull($user1->id, "upsert() should cause auto incremented PK value to be set on create");

        // 2. Upsert update
        $user2 = new TestUser();
        $user2->userName = "mr-upsert";
        $user2->enumValue = TestEnum::Two;
        $user2->upsert();

        $this->assertTrue($user2->upsert(), "upsert() should succeed on update");
        $this->assertSame($user1->id, $user2->id, "upsert() should cause auto incremented PK value to be set on update");

        // 3. Refetch to ensure data was updated
        /**
         * @var $userRefetched TestUser|null
         */
        $userRefetched = TestUser::query()
            ->where('user_name = ?', "mr-upsert")
            ->querySingleModel();

        $this->assertSame($userRefetched->enumValue, $user2->enumValue, "TestUser record should be updated in database after upsert()");
    }

    /**
     * @runInSeparateProcess 
     */
    public function testDelete()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        // 1. Insert user
        $newUser = new TestUser();
        $newUser->userName = "will-be-deleted";
        $newUser->save();
        
        // 2. Delete user
        $this->assertTrue($newUser->delete(), 'Delete should return true');
        
        // 3. Insert the user again, noting that no "duplicate key" exceptions are raised
        $newUser2 = new TestUser();
        $newUser2->userName = "will-be-deleted";
        $newUser2->save(); 
    }
    
    public function testFetch()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        // 1. Insert user
        $newUser = new TestUser();
        $newUser->userName = "imma-be-fetched-please";
        $newUser->save();
        
        // 2. Fetch user
        $fetchUser = TestUser::fetch($newUser->id);
        
        $this->assertEquals($newUser->id, $fetchUser->id, 'Fetch should return a single user object based on the primary key');
        $this->assertEquals('imma-be-fetched-please', $fetchUser->userName, 'Columns should be translated: userName should be filled with user_name value');
    }

    public function testFetchAll()
    {
        Instarecord::config(new TestDatabaseConfig());

        // 1. Insert user
        $newUser = new TestUser();
        $newUser->userName = "imma-be-listfetched-please";
        $newUser->save();

        // 2. Fetch user list
        $fetchUserList = TestUser::all();

        $this->assertNotEmpty($fetchUserList, 'Expected nonempty user list');

        $containsOurItem = false;
        
        foreach ($fetchUserList as $fetchUserListItem) {
            $this->assertInstanceOf("SoftwarePunt\\Instarecord\\Tests\\Samples\\TestUser", $fetchUserListItem,
                'Expected a list of user models');

            if ($fetchUserListItem->id == $newUser->id) {
                $containsOurItem = true;
            }
        }
        
        $this->assertTrue($containsOurItem, 'Expected to find our inserted user record in the all() list');

        // 3. Fetch all indexed
        $fetchAllIndexed = TestUser::allIndexed('userName');

        $this->assertNotEmpty($fetchAllIndexed, 'Expected nonempty user list');
        $this->assertArrayHasKey('imma-be-listfetched-please', $fetchAllIndexed,
            'Expected to find our inserted user record by name in the allIndexed() list');
    }
    
    public function testModelQueryUsesTableAndSelectAsDefaults()
    {
        $allUsersViaQuery = TestUser::query()->queryAllModels();
        $allUsersViaAll = TestUser::all();
        
        $this->assertEquals($allUsersViaQuery, $allUsersViaAll);
    }

    public function testModelQueryAllWithIndexedWithPrimaryKey()
    {
        $allUsersViaQuery = TestUser::query()->queryAllModelsIndexed();

        // -------------------------------------------------------------------------------------------------------------

        /**
         * @var $allUsersViaAll TestUser[]
         */
        $allUsersViaAll = TestUser::all();

        $userIds = [];

        foreach ($allUsersViaAll as $item) {
            $userIds[] = $item->id;
        }

        // -------------------------------------------------------------------------------------------------------------

        $valuesFromQuery = array_values($allUsersViaQuery);
        $valuesFromQuery = sort($valuesFromQuery);

        $keysFromQuery = array_keys($allUsersViaQuery);

        $valuesFromAll = array_values($allUsersViaAll);
        $valuesFromAll = sort($valuesFromAll);

        // -------------------------------------------------------------------------------------------------------------

        $this->assertEquals($valuesFromAll, $valuesFromQuery);
        $this->assertEquals(sort($userIds), sort($keysFromQuery));
    }

    public function testModelQueryAllWithIndexedWithCustomKey()
    {
        $allUsersViaQuery = TestUser::query()->queryAllModelsIndexed("userName");

        // -------------------------------------------------------------------------------------------------------------

        /**
         * @var $allUsersViaAll TestUser[]
         */
        $allUsersViaAll = TestUser::all();

        $userNames = [];

        foreach ($allUsersViaAll as $item) {
            $userNames[] = $item->userName;
        }

        // -------------------------------------------------------------------------------------------------------------

        $valuesFromQuery = array_values($allUsersViaQuery);
        $valuesFromQuery = sort($valuesFromQuery);

        $keysFromQuery = array_keys($allUsersViaQuery);

        $valuesFromAll = array_values($allUsersViaAll);
        $valuesFromAll = sort($valuesFromAll);

        // -------------------------------------------------------------------------------------------------------------

        $this->assertEquals($valuesFromAll, $valuesFromQuery);
        $this->assertEquals(sort($userNames), sort($keysFromQuery));
    }

    public function testModelInsertsFormattedValuesAndParsesIncomingValues()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Insert user with a formatted DateTime as their name, because why not
        $newUser = new TestUser();
        $testFormatStr = '1970-11-12 01:03:04';
        $newUser->joinDate = new \DateTime($testFormatStr);
        $newUser->enumValue = TestEnum::Two;
        $newUser->save();
        
        // The fact no errors have occurred is a good first step: it means we inserted valid data.
        // Now re-fetch into a new model, and ensure that we get a nice datetime object parsed from the db.
        $refetchedUser = TestUser::fetch($newUser->id);
        
        $this->assertInstanceOf('\DateTime', $refetchedUser->joinDate, 'Database DateTime value should have been parsed into a DateTime object');
        $this->assertEquals($testFormatStr, $refetchedUser->joinDate->format(Column::DATE_TIME_FORMAT), 'Database DateTime value should have been parsed correctly');
        $this->assertEquals(TestEnum::Two, $refetchedUser->enumValue, 'Database enum value should have been parsed correctly');
    }

    public function testModelUpdatesFormattedValues()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Create initial user
        $newUser = new TestUser();
        $newUser->userName = "testModelUpdatesFormattedValues";
        $newUser->save();
        
        // Update user with a new date time
        $testFormatStr = '1993-06-11 03:55:51';
        $newUser->joinDate = new \DateTime($testFormatStr);
        $newUser->update();

        // The fact no errors have occurred is a good first step: it means we updated the record with valid data.
        // Now re-fetch into a new model, and ensure that we get a nice datetime object parsed from the db.
        $refetchedUser = TestUser::fetch($newUser->id);

        $this->assertInstanceOf('\DateTime', $refetchedUser->joinDate, 'Database DateTime value should have been parsed into a DateTime object');
        $this->assertEquals($testFormatStr, $refetchedUser->joinDate->format(Column::DATE_TIME_FORMAT), 'Database DateTime value should have been parsed correctly');
    }
    
    public function testFetchReturnsNullForNoResult()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        $this->assertNull(TestUser::fetch(123123123));
    }
    
    public function testDefaultValuesForNonNullableDataTypes()
    {
        $newUser = new TestUser();
        
        $this->assertEquals('', $newUser->userName, "Non-nullable strings should be set to EMPTY STRING by default");
        $this->assertEquals(0, $newUser->id, "Non-nullable integers should be set to NULL by default");
        $this->assertFalse(isset($newUser->joinDate), "DateTimes should be unassigned (isset = false) until an explicit value is assigned");
    }
    
    public function testFetchPkVal()
    {
        $newUser = new TestUser();
        
        $this->assertEquals(0, $newUser->getPrimaryKeyValue());
        
        $newUser->id = 123;

        $this->assertEquals(123, $newUser->getPrimaryKeyValue());
    }

    public function testFetchExisting()
    {
        $existingJohn = new TestUser();
        $existingJohn->userName = 'John Is Real';

        $fetchResultOne = $existingJohn->fetchExisting();

        $existingJohn->save();

        $matchingJohn = new TestUser();
        $matchingJohn->userName = 'John Is Real';

        $fetchResultTwo = $matchingJohn->fetchExisting();

        $this->assertNull($fetchResultOne, 'fetchExisting() 1 should return NULL initially, as no data exists');
        $this->assertEquals($existingJohn, $fetchResultTwo, 'fetchExisting() 2 should return a copy of the initial object since all properties match');
    }

    public function testTryBecomeExisting()
    {
        $someDt = new \DateTime('now');

        // Create the initial record, which we'll be trying to "become"
        $existingJohn = new TestUser();
        $existingJohn->userName = 'John Is The OG';
        $existingJohn->joinDate = $someDt;
        $existingJohn->save();

        // Try a failing scenario
        $matchingJohn = new TestUser();
        $matchingJohn->userName = 'Mike Is The OG No Match Here';
        $matchingJohn->joinDate = $someDt;

        $this->assertFalse($matchingJohn->tryBecomeExisting(), 'tryBecomeExisting() should fail if no match is found');
        $this->assertEmpty($matchingJohn->id, 'tryBecomeExisting() should not set a PK ID if it returns false');

        // Try a winning scenario
        $matchingJohn = new TestUser();
        $matchingJohn->userName = 'John Is The OG';
        $matchingJohn->joinDate = $someDt;

        $this->assertTrue($matchingJohn->tryBecomeExisting(), 'tryBecomeExisting() should return true when a match is found');
        $this->assertNotEmpty($existingJohn->id, 'tryBecomeExisting() should set properties from the fetched model (initial model should have a valid id to test)');
        $this->assertEquals($matchingJohn->id, $existingJohn->id, 'tryBecomeExisting() should set properties from the fetched model');
    }

    public function testReadWriteSerializedType()
    {
        $user = new TestUserWithSerialized();
        $this->assertNull($user->userName, "By default, a nullable serialized type should have a NULL value");

        $user->userName = new TestDummySerializableType("Mr. Hands");
        $this->assertTrue($user->save(), "Saving a serializable object value should succeed");

        $user = TestUser::fetch($user->id);
        $this->assertSame("Mr. Hands", $user->userName, "Reading a serialized value as string should work");

        $user = TestUserWithSerialized::fetch($user->id);
        $this->assertEquals(new TestDummySerializableType("Mr. Hands"), $user->userName, "Reading a serialized object from database should work");
    }

    public function testTrySave()
    {
        $user = new TestUser();

        $exceptionThrown = false;
        try {
            $user->save();
        } catch (\Exception $ex) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, "regular save() should trigger regular exception");

        $this->assertFalse($user->trySave(), "trySave() should suppress exception and return false");
    }
}
