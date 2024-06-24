<?php


use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Attributes\Required;

class RequiredTest extends TestCase
{
    public function testRequiredValidation()
    {
        $validator = new Required();

        $this->assertTrue($validator->checkValue("some text"), "Expecting Pass: non-empty string value");
        $this->assertTrue($validator->checkValue("0"), "Expecting Pass: string '0'");
        $this->assertTrue($validator->checkValue(new stdClass()), "Expecting Pass: object");

        $this->assertFalse($validator->checkValue(null), "Expecting Fail: null");
        $this->assertFalse($validator->checkValue(""), "Expecting Fail: empty string");
        $this->assertFalse($validator->checkValue(" \r\n \t "), "Expecting Fail: space/tab/newline");
        $this->assertFalse($validator->checkValue([]), "Expecting Fail: empty array");
        $this->assertFalse($validator->checkValue(0), "Expecting Fail: zero");
        $this->assertFalse($validator->checkValue(false), "Expecting Fail: false");
    }
}