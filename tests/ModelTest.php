<?php

namespace Instasell\Instarecord\Tests;

use Instasell\Instarecord\DatabaseAdapter;
use Instasell\Instarecord\Instarecord;
use Instasell\Instarecord\Tests\Samples\User;
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
        
        $this->assertEquals($values['id'], $user->getProperties()['id']);
        $this->assertEquals($values['userName'], $user->getProperties()['userName']);
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
    
    public function testGetTableName()
    {
        $sampleUserModel = new User();
        $this->assertEquals('users', $sampleUserModel->getTableName());
    }

    /**
     * @runInSeparateProcess 
     */
    public function testCreateSimple()
    {
        $config = Instarecord::config();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;

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
        $config = Instarecord::config();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;
        
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
        $config = Instarecord::config();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;

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
        $config = Instarecord::config();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;
        
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
        $config = Instarecord::config();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;
        
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
        $config = Instarecord::config();
        $config->adapter = DatabaseAdapter::MYSQL;
        $config->username = TEST_USER_NAME;
        $config->password = TEST_PASSWORD;
        $config->database = TEST_DATABASE_NAME;

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
}
