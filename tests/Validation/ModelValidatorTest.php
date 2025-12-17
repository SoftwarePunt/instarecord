<?php

use PHPUnit\Framework\TestCase;
use Softwarepunt\Instarecord\Tests\Samples\TestBackedEnum;
use SoftwarePunt\Instarecord\Tests\Samples\TestUser;

class ModelValidatorTest extends TestCase
{
    public function testValidationSuccess()
    {
        $sample = new TestUser();
        $sample->userName = "abc";
        $sample->joinDate = new DateTime('now');
        $sample->enumValue = TestBackedEnum::Three;

        $result = $sample->validate();

        $this->assertTrue($result->ok, "Validation should pass");
        $this->assertEmpty($result->messages, "Validation with pass should have no messages");
    }

    public function testValidationFailure()
    {
        $sample = new TestUser();
        $sample->userName = "abcdefg";

        $result = $sample->validate();

        $this->assertFalse($result->ok, "Validation should fail");
        $this->assertNotEmpty($result->messages, "Validation with fail should have messages");
        $this->assertEquals([
            "Your name is too darn long!", // custom error
            "thee date is required", // default error with custom name
            "Enum Value is required" // default error with fallback name
        ], $result->messages, "Validation message should match");
    }
}