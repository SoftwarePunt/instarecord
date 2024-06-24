<?php


namespace SoftwarePunt\Instarecord\Tests\Validation;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Attributes\MaxLength;
use SoftwarePunt\Instarecord\Attributes\MinLength;
use SoftwarePunt\Instarecord\Attributes\Required;

class MaxLengthTest extends TestCase
{
    public function testRequiredValidation()
    {
        $validator = new MaxLength(3);

        $this->assertTrue($validator->checkValue(null), "Expecting Pass: null");
        $this->assertTrue($validator->checkValue(""), "Expecting Pass: empty string");
        $this->assertTrue($validator->checkValue("ab"), "Expecting Pass: string value, 2 chars");
        $this->assertTrue($validator->checkValue("abc"), "Expecting Pass: string value, 3 chars");

        $this->assertFalse($validator->checkValue("abcd"), "Expecting Fail: too long string");
    }
}