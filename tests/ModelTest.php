<?php

namespace Instasell\Instarecord\Tests;

use Instasell\Instarecord\Tests\Samples\User;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
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
}
