<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileUpload;
use App\Kernel\Form\Validator\Assert\File;

class FileTest extends TestCase
{
    public function testReturnsTrueWhenNullIsPassed(): void
    {
        $this->assertTrue((new File(2097152))->validate(null));
    }

    public function testReturnsFalseWhenValueIsString(): void
    {
        $this->assertFalse((new File(2097152))->validate('not_a_file'));
    }

    public function testReturnsFalseWhenValueIsInteger(): void
    {
        $this->assertFalse((new File(2097152))->validate(1));
    }

    public function testReturnsFalseWhenValueIsArray(): void
    {
        $this->assertFalse((new File(2097152))->validate([]));
    }

    public function testReturnsFalseWhenValueIsRawFilesArray(): void
    {
        // raw $_FILES array — must be a FileUpload instance, not a plain array
        $this->assertFalse((new File(2097152))->validate([
            'name'     => 'photo.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => '/tmp/php1234',
            'size'     => 1024,
            'error'    => 0,
        ]));
    }

    public function testReturnsFalseWhenValueIsBool(): void
    {
        $this->assertFalse((new File(2097152))->validate(true));
    }

    public function testReturnsFalseWhenValueIsObject(): void
    {
        $this->assertFalse((new File(2097152))->validate(new \stdClass()));
    }

    public function testReturnsTrueWhenFileUploadIsValid(): void
    {
        $file =   $this->createStub(FileUpload::class);
        $file->method('isValid')->willReturn(true);
        $this->assertTrue((new File(2097152))->validate($file));
    }

    public function testReturnsFalseWhenFileUploadIsInvalid(): void
    {
        $file = $this->createStub(FileUpload::class);
        $file->method('isValid')->willReturn(false);
        $this->assertFalse((new File(2097152))->validate($file));
    }

    public function testMaxSizeIsPassedToIsValid(): void
    {
        $maxSize = 1048576; // 1MB
        $file = $this->createMock(FileUpload::class);
        $file->expects($this->once())
            ->method('isValid')
            ->with($maxSize, [], [])
            ->willReturn(true);
        (new File($maxSize))->validate($file);
    }


    public function testAllowedMimeTypesArePassedToIsValid(): void
    {
        $mimeTypes = ['image/jpeg', 'image/png'];
        $file = $this->createMock(FileUpload::class);
        $file->expects($this->once())
            ->method('isValid')
            ->with(2097152, $mimeTypes, [])
            ->willReturn(true);
        (new File(2097152, $mimeTypes))->validate($file);
    }

    public function testAllowedExtensionsArePassedToIsValid(): void
    {
        $extensions = ['jpg', 'png'];
        $file = $this->createMock(FileUpload::class);
        $file->expects($this->once())
            ->method('isValid')
            ->with(2097152, [], $extensions)
            ->willReturn(true);
        (new File(2097152, [], $extensions))->validate($file);
    }

    public function testAllParametersArePassedToIsValid(): void
    {
        $maxSize    = 512000;
        $mimeTypes  = ['application/pdf'];
        $extensions = ['pdf'];
        $file = $this->createMock(FileUpload::class);
        $file->expects($this->once())
            ->method('isValid')
            ->with($maxSize, $mimeTypes, $extensions)
            ->willReturn(true);
        (new File($maxSize, $mimeTypes, $extensions))->validate($file);
    }

    public function testDefaultErrorMessageIsSet(): void
    {
        $this->assertSame(
            'property %s contains an invalid file',
            (new File(2097152))->getMessage()
        );
    }

    public function testCustomErrorMessageOverridesDefault(): void
    {
        $assert = new File(2097152, [], [], 'field %s has an invalid file');
        $this->assertSame('field %s has an invalid file', $assert->getMessage());
    }
}
