<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Form\Validator\Assert\Url;

class UrlTest extends TestCase
{
     public function testReturnsTrueWhenNullIsPassed(): void
    {
        $this->assertTrue((new Url())->validate(null));
    }

    public function testReturnsFalseOnInteger(): void
    {
        $this->assertFalse((new Url())->validate(1));
    }

    public function testReturnsFalseOnBool(): void
    {
        $this->assertFalse((new Url())->validate(true));
    }

    public function testReturnsFalseOnArray(): void
    {
        $this->assertFalse((new Url())->validate([]));
    }

    public function testReturnsFalseOnObject(): void
    {
        $this->assertFalse((new Url())->validate(new \stdClass()));
    }

    public function testReturnsTrueOnHttpUrl(): void
    {
        $this->assertTrue((new Url())->validate('http://example.com'));
    }

    public function testReturnsTrueOnHttpsUrl(): void
    {
        $this->assertTrue((new Url())->validate('https://example.com'));
    }

    public function testReturnsTrueOnFtpUrl(): void
    {
        $this->assertTrue((new Url())->validate('ftp://example.com'));
    }

    public function testReturnsFalseOnUnknownSchema(): void
    {
        $this->assertFalse((new Url())->validate('mailto:user@example.com'));
    }

    public function testReturnsFalseOnSchemaMissing(): void
    {
        $this->assertFalse((new Url())->validate('example.com'));
    }

    public function testReturnsFalseOnSchemaOnlyNoHost(): void
    {
        $this->assertFalse((new Url())->validate('https://'));
    }

    public function testReturnsTrueOnUrlWithPath(): void
    {
        $this->assertTrue((new Url())->validate('https://example.com/some/page'));
    }

    public function testReturnsTrueOnUrlWithQueryString(): void
    {
        $this->assertTrue((new Url())->validate('https://example.com?foo=bar&baz=qux'));
    }

    public function testReturnsTrueOnUrlWithPort(): void
    {
        $this->assertTrue((new Url())->validate('http://example.com:8080'));
    }

    public function testReturnsTrueOnUrlWithPortAndPath(): void
    {
        $this->assertTrue((new Url())->validate('http://example.com:8080/some/page'));
    }

    public function testReturnsTrueOnIpAddressHost(): void
    {
        $this->assertTrue((new Url())->validate('http://192.168.1.1'));
    }

    public function testReturnsTrueOnIpAddressHostWithPort(): void
    {
        $this->assertTrue((new Url())->validate('http://192.168.1.1:8080'));
    }

    public function testReturnsTrueOnIpAddressHostWithPath(): void
    {
        $this->assertTrue((new Url())->validate('http://192.168.1.1/some/page'));
    }

    // ── invalid URL formats ───────────────────────────────────────────────────

    public function testReturnsTrueOnUrlWithFragment(): void
    {
        $this->assertTrue((new Url())->validate('https://example.com#anchor'));
    }

    public function testReturnsFalseOnEmptyString(): void
    {
        $this->assertFalse((new Url())->validate(''));
    }

    public function testReturnsFalseOnRandomString(): void
    {
        $this->assertFalse((new Url())->validate('not a url at all'));
    }

    public function testReturnsFalseOnUrlWithSpaces(): void
    {
        $this->assertFalse((new Url())->validate('https://exam ple.com'));
    }

    public function testReturnsTrueOnFullyFeaturedUrl(): void
    {
        // path + query string + port all together
        $this->assertTrue((new Url())->validate('https://example.com:8080/some/page?foo=bar&baz=qux'));
    }

    public function testReturnsTrueOnFtpWithPath(): void
    {
        $this->assertTrue((new Url())->validate('ftp://files.example.com/some/file.txt'));
    }

    public function testDefaultErrorMessageIsSet(): void
    {
        $this->assertSame('property %s must be a valid URL', (new Url())->getMessage());
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $assert = new Url('field %s is not a valid URL');
        $this->assertSame('field %s is not a valid URL', $assert->getMessage());
    }
}