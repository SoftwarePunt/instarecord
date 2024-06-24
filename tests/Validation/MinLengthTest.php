<?php


namespace SoftwarePunt\Instarecord\Tests\Validation;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Attributes\MinLength;
use SoftwarePunt\Instarecord\Attributes\Required;

class MinLengthTest extends TestCase
{
    public function testRequiredValidation()
    {
        $validator = new MinLength(3);

        $this->assertTrue($validator->checkValue("some text"), "Expecting Pass: string value, > 3 chars");
        $this->assertTrue($validator->checkValue(123), "Expecting Pass: long numeric value, 3 chars");

        $this->assertFalse($validator->checkValue(null), "Expecting Fail: null");
        $this->assertFalse($validator->checkValue(""), "Expecting Fail: empty string");
        $this->assertFalse($validator->checkValue("ab"), "Expecting Fail: too short string");
        $this->assertFalse($validator->checkValue(12), "Expecting Fail: too short numeric value");
        $this->assertFalse($validator->checkValue(true), "Expecting Fail: bool");
        $this->assertFalse($validator->checkValue(false), "Expecting Fail: bool");
    }
}