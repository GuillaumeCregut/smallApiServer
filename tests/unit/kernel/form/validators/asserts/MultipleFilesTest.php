<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileUpload;
use App\Kernel\Form\Validator\Assert\Multiplefiles;

class MultipleFilesTest extends TestCase
{
    public function testReturnsTrueWhenNullIsPassed(): void
    {
        $this->assertTrue((new Multiplefiles(2097152))->validate(null));
    }

    public function testReturnsFalseWhenValueIsString(): void
    {
        $this->assertFalse((new Multiplefiles(2097152))->validate('not_a_file'));
    }

    public function testReturnsFalseWhenValueIsInteger(): void
    {
        $this->assertFalse((new Multiplefiles(2097152))->validate(1));
    }

    public function testReturnsFalseWhenValueIsArray(): void
    {
        $this->assertFalse((new Multiplefiles(2097152))->validate([]));
    }

    public function testReturnsFalseWhenValueIsRawFilesArray(): void
    {
        // raw $_FILES array — must be a FileUpload instance, not a plain array
        $this->assertFalse((new Multiplefiles(2097152))->validate([
            'name'     => 'photo.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => '/tmp/php1234',
            'size'     => 1024,
            'error'    => 0,
        ]));
    }

    public function testReturnsFalseWhenValueIsBool(): void
    {
        $this->assertFalse((new Multiplefiles(2097152))->validate(true));
    }

    public function testReturnsFalseWhenValueIsObject(): void
    {
        $this->assertFalse((new Multiplefiles(2097152))->validate(new \stdClass()));
    }

    public function testReturnsTrueWhenFileUploadIsValid(): void
    {
        $file =   $this->createStub(FileUpload::class);
        $file->method('isValid')->willReturn(true);
        $this->assertTrue((new Multiplefiles(2097152))->validate([$file]));
    }

    public function testReturnsTrueWhenAllFilesAreValid(): void
    {
        $file1 = $this->createStub(FileUpload::class);
        $file1->method('isValid')->willReturn(true);
        $file2 = $this->createStub(FileUpload::class);
        $file2->method('isValid')->willReturn(true);
        $this->assertTrue((new MultipleFiles(2097152))->validate([$file1, $file2]));
    }

    public function testReturnsFalseWhenOneFileIsInvalid(): void
    {
        $file1 = $this->createStub(FileUpload::class);
        $file1->method('isValid')->willReturn(true);
        $file2 = $this->createStub(FileUpload::class);
        $file2->method('isValid')->willReturn(false);
        $this->assertFalse((new MultipleFiles(2097152))->validate([$file1, $file2]));
    }

    public function testReturnsFalseWhenAllFilesAreInvalid(): void
    {
        $file1 = $this->createStub(FileUpload::class);
        $file1->method('isValid')->willReturn(false);
        $file2 = $this->createStub(FileUpload::class);
        $file2->method('isValid')->willReturn(false);
        $this->assertFalse((new MultipleFiles(2097152))->validate([$file1, $file2]));
    }

    public function testReturnsFalseWhenArrayContainsNonFileUpload(): void
    {
        $file = $this->createStub(FileUpload::class);
        $file->method('isValid')->willReturn(true);
        $this->assertFalse((new MultipleFiles(2097152))->validate([$file, 'not_a_file']));
    }
}
