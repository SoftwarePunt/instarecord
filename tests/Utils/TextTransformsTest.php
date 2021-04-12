<?php

namespace Softwarepunt\Instarecord\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Softwarepunt\Instarecord\Utils\TextTransforms;

class TextTransformsTest extends TestCase
{
    public function testPluralize()
    {
        $this->assertSame("quizzes", TextTransforms::pluralize("quiz"));
        $this->assertSame("moves", TextTransforms::pluralize("move"));
        $this->assertSame("sheep", TextTransforms::pluralize("sheep"));
        $this->assertSame("zs", TextTransforms::pluralize("z"));
        $this->assertSame("xes", TextTransforms::pluralize("x"));
        $this->assertSame("s", TextTransforms::pluralize("s"));
    }

    public function testSingularize()
    {
        $this->assertSame("quiz", TextTransforms::singularize("quizzes"));
        $this->assertSame("move", TextTransforms::singularize("moves"));
        $this->assertSame("sheep", TextTransforms::singularize("sheep"));
        $this->assertSame("z", TextTransforms::singularize("zs"));
        $this->assertSame("x", TextTransforms::singularize("xes"));
        $this->assertSame("", TextTransforms::singularize("s"));
    }

    public function testRemoveNamespaceFromClassName()
    {
        $this->assertSame("class", TextTransforms::removeNamespaceFromClassName("my\\name\\space\\class"));
    }
}
