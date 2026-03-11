<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Sanitizer\DataSanitizer;

class SanitizerTest extends TestCase
{
    public function testTrimsWhitespace(): void
    {
        $result = DataSanitizer::sanitize(['name' => '  John  ']);
        $this->assertSame('John', $result['name']);
    }

    public function testStripsHtmlTags(): void
    {
        $result = DataSanitizer::sanitize(['name' => '<b>John</b>']);
        $this->assertSame('John', $result['name']);
    }

    public function testEscapesHtmlSpecialChars(): void
    {
        $result = DataSanitizer::sanitize(['name' => 'John & "Doe"']);
        $this->assertSame('John &amp; "Doe"', $result['name']);
    }

    public function testCombinesAllStringRules(): void
    {
        $result = DataSanitizer::sanitize(['name' => '  <b>John & Doe</b>  ']);
        $this->assertSame('John &amp; Doe', $result['name']);
    }

    public function testEmptyStringRemainsEmpty(): void
    {
        $result = DataSanitizer::sanitize(['name' => '']);
        $this->assertSame('', $result['name']);
    }

    public function testCleanStringIsUntouched(): void
    {
        $result = DataSanitizer::sanitize(['name' => 'John']);
        $this->assertSame('John', $result['name']);
    }

    public function testIntegerIsUntouched(): void
    {
        $result = DataSanitizer::sanitize(['age' => 42]);
        $this->assertSame(42, $result['age']);
    }

    public function testFloatIsUntouched(): void
    {
        $result = DataSanitizer::sanitize(['price' => 9.99]);
        $this->assertSame(9.99, $result['price']);
    }

    public function testBooleanIsUntouched(): void
    {
        $result = DataSanitizer::sanitize(['active' => true]);
        $this->assertTrue($result['active']);
    }

    public function testNullIsUntouched(): void
    {
        $result = DataSanitizer::sanitize(['field' => null]);
        $this->assertNull($result['field']);
    }

    public function testSanitizesAllStringFieldsInArray(): void
    {
        $result = DataSanitizer::sanitize([
            'name'      => '  John  ',
            'firstname' => '<i>Doe</i>',
            'age'       => 30,
        ]);

        $this->assertSame('John', $result['name']);
        $this->assertSame('Doe', $result['firstname']);
        $this->assertSame(30, $result['age']);
    }

    public function testEmptyArrayReturnsEmptyArray(): void
    {
        $result = DataSanitizer::sanitize([]);
        $this->assertSame([], $result);
    }

    public function testSanitizesNestedArray(): void
    {
        $result = DataSanitizer::sanitize([
            'address' => [
                'city'    => '  <b>Paris</b>  ',
                'zipcode' => 75000,
            ]
        ]);

        $this->assertSame('Paris', $result['address']['city']);
        $this->assertSame(75000, $result['address']['zipcode']);
    }

    public function testSanitizesDeeplyNestedArray(): void
    {
        $result = DataSanitizer::sanitize([
            'level1' => [
                'level2' => [
                    'value' => '  <script>alert(1)</script>  ',
                ]
            ]
        ]);

        $this->assertSame('alert(1)', $result['level1']['level2']['value']);
    }
}
