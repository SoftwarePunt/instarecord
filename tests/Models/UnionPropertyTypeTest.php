<?php

namespace SoftwarePunt\Instarecord\Tests\Models;

use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Instarecord;
use Softwarepunt\Instarecord\Tests\Samples\TestBackedEnum;
use SoftwarePunt\Instarecord\Tests\Samples\TestUnionModel;

require_once __DIR__ . "/../Samples/TestBackedEnum.php";

class UnionPropertyTypeTest extends TestCase
{
    public function testUnionTypeSerialize(): void
    {
        $model = new TestUnionModel();

        // Mixed (whoKnows)
        $this->assertSame(null, $model->getColumnValues()['who_knows'],
            "Nullable union type should be initialized as null");

        $model->whoKnows = 1;
        $this->assertSame("1", $model->getColumnValues()['who_knows']);

        $model->whoKnows = "1";
        $this->assertSame("1", $model->getColumnValues()['who_knows']);

        $model->whoKnows = TestBackedEnum::One;
        $this->assertSame("one", $model->getColumnValues()['who_knows']);

        $model->whoKnows = null;
        $this->assertSame(null, $model->getColumnValues()['who_knows']);

        // intOrNull
        $this->assertSame(null, $model->getColumnValues()['int_or_null'],
            "Nullable union type should be initialized as null");

        $model->intOrNull = 123;
        $this->assertSame("123", $model->getColumnValues()['int_or_null']);

        $model->intOrNull = null;
        $this->assertSame(null, $model->getColumnValues()['int_or_null']);

        // prefScalar
        $this->assertSame(0.0, $model->prefScalar,
            "Non-nullable union float|int|bool type should be initialized as 0.0");

        // Note: Instarecord currently assumes/formats with 4 decimals for decimal types

        $model->prefScalar = 123.456;
        $this->assertSame("123.4560", $model->getColumnValues()['pref_scalar']);

        $model->prefScalar = 123;
        $this->assertSame("123.0000", $model->getColumnValues()['pref_scalar']);

        $model->prefScalar = true;
        $this->assertSame("1", $model->getColumnValues()['pref_scalar']); // note: bool format is hardcoded

        $model->prefScalar = false;
        $this->assertSame("0", $model->getColumnValues()['pref_scalar']); // note: bool format is hardcoded
    }

    public function testUnionTypeDeserialize(): void
    {
        // Mixed (whoKnows)
        $model = new TestUnionModel(['who_knows' => '1']);
        $this->assertSame("1", $model->whoKnows);

        $model = new TestUnionModel(['who_knows' => 'one']);
        $this->assertSame("one", $model->whoKnows);

        // intOrNull
        $model = new TestUnionModel(['int_or_null' => '123']);
        $this->assertSame(123, $model->intOrNull);

        // prefScalar
        $model = new TestUnionModel(['pref_scalar' => '123.456']);
        $this->assertSame(123.456, $model->prefScalar);

        $model = new TestUnionModel(['pref_scalar' => '123']);
        $this->assertSame(123.0, $model->prefScalar);

        $model = new TestUnionModel(['pref_scalar' => false]);
        $this->assertSame(0.0, $model->prefScalar);
    }
}