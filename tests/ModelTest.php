<?php

namespace Instasell\Instarecord\Tests;

use Instasell\Instarecord\Database\Column;
use Instasell\Instarecord\DatabaseAdapter;
use Instasell\Instarecord\Instarecord;
use Instasell\Instarecord\Tests\Database\DataFormattingTest;
use Instasell\Instarecord\Tests\Samples\User;
use Instasell\Instarecord\Tests\Testing\TestDatabaseConfig;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testConstructWithDefaults()
    {
        $user = new User([
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
        
        $user = new User($values);
        
        $this->assertEquals($values['id'], $user->getPropertyValues()['id']);
        $this->assertEquals($values['userName'], $user->getPropertyValues()['userName']);
    }
    
    public function testGetDirtyProperties()
    {
        $values = [
            'id' => 123,
            'userName' => 'Henk'
        ];

        $user = new User($values);
        
        $this->assertEmpty($user->getDirtyProperties());
        
        $user->userName = 'Jan';

        $this->assertEquals(['userName' => 'Jan'], $user->getDirtyProperties());
    }
    
    public function testGetDirtyColumns()
    {
        $values = [
            'id' => 123,
            'user_name' => 'Henk'
        ];

        $user = new User($values);

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

        $user = new User($values);

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

        $user = new User($values);

        $user->markAllPropertiesDirty();

        $this->assertNotEmpty($user->getDirtyProperties());
        
        $user->markAllPropertiesClean();

        $this->assertEmpty($user->getDirtyProperties());
    }
    
    public function testGetPropertyNames()
    {
        $sampleUserModel = new User();
        $propertyList = $sampleUserModel->getPropertyNames();
        
        $this->assertNotEmpty($propertyList, 'A property list should not be empty');
        $this->assertContains('id', $propertyList, 'The "id" property should be returned');
        $this->assertContains('userName', $propertyList, 'The "user_name" property should be returned');
        $this->assertNotContains('secretNotWritable', $propertyList, 'Private properties should not be included in property list');
    }
    
    public function testGetColumnNames()
    {
        $sampleUserModel = new User();
        $propertyList = $sampleUserModel->getColumnNames();

        $this->assertNotEmpty($propertyList, 'A property list should not be empty');
        $this->assertContains('id', $propertyList, 'The "id" property should be returned');
        $this->assertContains('user_name', $propertyList, 'The "user_name" property should be returned and translated');
        $this->assertNotContains('secretNotWritable', $propertyList, 'Private properties should not be included in property list');
    }

    public function testGetColumnNameForPropertyName()
    {
        $sampleUserModel = new User();
        $this->assertEquals('id', $sampleUserModel->getColumnNameForPropertyName('id'));
        $this->assertEquals('user_name', $sampleUserModel->getColumnNameForPropertyName('userName'));
    }

    public function testGetPropertyNameForColumnName()
    {
        $sampleUserModel = new User();
        $this->assertEquals('id', $sampleUserModel->getPropertyNameForColumnName('id'));
        $this->assertEquals('userName', $sampleUserModel->getPropertyNameForColumnName('user_name'));
    }
    
    public function testGetTableName()
    {
        $sampleUserModel = new User();
        $this->assertEquals('users', $sampleUserModel->getTableName());
    }

    public function testSetColumnValues()
    {
        $user = new User();
        $user->setColumnValues([
            'id' => 5,
            'user_name' => 'Bob'
        ]);

        $this->assertEquals('Bob', $user->userName);
        $this->assertEquals(5, $user->id);
        $this->assertNull($user->joinDate);
    }

    public function testGetAndSetColumnValues()
    {
        $user = new User();

        $testSet = [
            'id' => 5,
            'user_name' => 'Bob'
        ];

        $user->setColumnValues($testSet);

        $actualFromGet = $user->getColumnValues();

        $this->assertEquals($testSet['id'], $actualFromGet['id']);
        $this->assertEquals($testSet['user_name'], $actualFromGet['user_name']);
    }

    /**
     * @runInSeparateProcess 
     */
    public function testCreateSimple()
    {
        Instarecord::config(new TestDatabaseConfig());

        $newUser = new User();
        $newUser->userName = "my-test-user-one";
        
        $this->assertTrue($newUser->create(), 'Creating a new record should return TRUE');
        $this->assertNotEmpty($newUser->id, 'Creating a new record should update its primary key');
        $this->assertEmpty($newUser->getDirtyProperties(), 'After creating an item, all properties should be clean');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateViaSave()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        $newUser = new User();
        $newUser->userName = "my-test-user-two";

        $this->assertTrue($newUser->save(), 'Creating a new record should return TRUE (via save)');
        $this->assertNotEmpty($newUser->id, 'Creating a new record should update its primary key (via save)');
        $this->assertEmpty($newUser->getDirtyProperties(), 'After saving an item, all properties should be clean');
    }

    /**
     * @depends testCreateViaSave
     * @runInSeparateProcess 
     */
    public function testUpdateCreatedRecordViaSave()
    {
        Instarecord::config(new TestDatabaseConfig());

        // 1. Insert user
        $newUser = new User();
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
    public function testDelete()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        // 1. Insert user
        $newUser = new User();
        $newUser->userName = "will-be-deleted";
        $newUser->save();
        
        // 2. Delete user
        $this->assertTrue($newUser->delete(), 'Delete should return true');
        
        // 3. Insert the user again, noting that no "duplicate key" exceptions are raised
        $newUser2 = new User();
        $newUser2->userName = "will-be-deleted";
        $newUser2->save(); 
    }
    
    public function testFetch()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        // 1. Insert user
        $newUser = new User();
        $newUser->userName = "imma-be-fetched-please";
        $newUser->save();
        
        // 2. Fetch user
        $fetchUser = User::fetch($newUser->id);
        
        $this->assertEquals($newUser->id, $fetchUser->id, 'Fetch should return a single user object based on the primary key');
        $this->assertEquals('imma-be-fetched-please', $fetchUser->userName, 'Columns should be translated: userName should be filled with user_name value');
    }

    public function testFetchAll()
    {
        Instarecord::config(new TestDatabaseConfig());

        // 1. Insert user
        $newUser = new User();
        $newUser->userName = "imma-be-listfetched-please";
        $newUser->save();

        // 2. Fetch user list
        $fetchUserList = User::all();

        $this->assertNotEmpty($fetchUserList, 'Expected nonempty user list');

        $containsOurItem = false;
        
        foreach ($fetchUserList as $fetchUserListItem) {
            $this->assertInstanceOf("Instasell\\Instarecord\\Tests\\Samples\\User", $fetchUserListItem, 'Expected a list of user models');
            
            if ($fetchUserListItem->id === $newUser->id) {
                $containsOurItem = true;
            }
        }
        
        $this->assertTrue($containsOurItem, 'Expected to find our inserted user record in the all() list');
    }
    
    public function testModelQueryUsesTableAndSelectAsDefaults()
    {
        $allUsersViaQuery = User::query()->queryAllModels();
        $allUsersViaAll = User::all();
        
        $this->assertEquals($allUsersViaQuery, $allUsersViaAll);
    }

    public function testModelInsertsFormattedValuesAndParsesIncomingValues()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Insert user with a formatted DateTime as their name, because why not
        $newUser = new User();
        $testFormatStr = '1970-11-12 01:03:04';
        $newUser->joinDate = new \DateTime($testFormatStr);
        $newUser->save();
        
        // The fact no errors have occurred is a good first step: it means we inserted valid data.
        // Now re-fetch into a new model, and ensure that we get a nice datetime object parsed from the db.
        $refetchedUser = User::fetch($newUser->id);
        
        $this->assertInstanceOf('\DateTime', $refetchedUser->joinDate, 'Database DateTime value should have been parsed into a DateTime object');
        $this->assertEquals($testFormatStr, $refetchedUser->joinDate->format(Column::DATE_TIME_FORMAT), 'Database DateTime value should have been parsed correctly');
    }

    public function testModelUpdatesFormattedValues()
    {
        Instarecord::config(new TestDatabaseConfig());

        // Create initial user
        $newUser = new User();
        $newUser->joinDate = null;
        $newUser->save();
        
        // Update user with a new date time
        $testFormatStr = '1993-06-11 03:55:51';
        $newUser->joinDate = new \DateTime($testFormatStr);
        $newUser->update();

        // The fact no errors have occurred is a good first step: it means we updated the record with valid data.
        // Now re-fetch into a new model, and ensure that we get a nice datetime object parsed from the db.
        $refetchedUser = User::fetch($newUser->id);

        $this->assertInstanceOf('\DateTime', $refetchedUser->joinDate, 'Database DateTime value should have been parsed into a DateTime object');
        $this->assertEquals($testFormatStr, $refetchedUser->joinDate->format(Column::DATE_TIME_FORMAT), 'Database DateTime value should have been parsed correctly');
    }
    
    public function testFetchReturnsNullForNoResult()
    {
        Instarecord::config(new TestDatabaseConfig());
        
        $this->assertNull(User::fetch(123123123));
    }
}
