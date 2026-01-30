<?php

use PHPUnit\Framework\TestCase;
use App\Kernel\Files\FileFormator;

Class FileFormatorTest  extends TestCase
{
    public function testFileFormator()
    {
       $files =[];
       $this->assertEquals($files, FileFormator::convert([]));
    }

    public function testFileFormatorSingle()
    {
        $files = [
            'documents' => [
                'name' => 'file2.pdf',
                'type' => 'application/pdf',
                'tmp_name' =>  '/tmp/phpYzdqkE',
                'error' => 0,
                'size' => 12345,
                'full_path' => 'toto'
            ],
        ];

        $expected = [
            'documents' => [
                [ 
                    'name' => 'file2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkE',
                    'error' => 0,
                    'size' => 12345,
                    'full_path' => 'toto'
                ],
            ],
        ];

        $this->assertEquals($expected, FileFormator::convert($files));
    }

public function testFileFormatorSingle2Fields()
    {
        $files = [
            'documents' => [
                'name' => 'file2.pdf',
                'type' => 'application/pdf',
                'tmp_name' =>  '/tmp/phpYzdqkE',
                'error' => 0,
                'size' => 12345,
                'full_path' => 'toto'
            ],
             'titi' => [
                'name' => 'file2.pdf',
                'type' => 'application/pdf',
                'tmp_name' =>  '/tmp/phpYzdqkE',
                'error' => 0,
                'size' => 12345,
                'full_path' => 'toto'
            ],
        ];

        $expected = [
            'documents' => [
                [ 
                    'name' => 'file2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkE',
                    'error' => 0,
                    'size' => 12345,
                    'full_path' => 'toto'
                ],
            ],
            'titi' => [
                [ 
                    'name' => 'file2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkE',
                    'error' => 0,
                    'size' => 12345,
                    'full_path' => 'toto'
                ],
            ],
        ];

        $this->assertEquals($expected, FileFormator::convert($files));
    }


    public function testFileFormatorMultiple()
    {
        $files = [
            'documents' => [
                'name' => ['file1.pdf', 'file2.pdf'],
                'type' => ['application/pdf', 'application/pdf'],
                'tmp_name' => ['/tmp/phpYzdqkD', '/tmp/phpYzdqkE'],
                'error' => [0, 0],
                'size' => [12345, 67890],
                'full_path' => ['toto','titi']
            ],
        ];

        $expected = [
            'documents' => [
                [
                    'name' => 'file1.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkD',
                    'error' => 0,
                    'size' => 12345,
                    'full_path' => 'toto'
                ],
                [
                    'name' => 'file2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkE',
                    'error' => 0,
                    'size' => 67890,
                    'full_path' => 'titi'
                ],
            ],
        ];

        $this->assertEquals($expected, FileFormator::convert($files));
    }

     public function testFileFormatorMultiple2Fields()
    {
        $files = [
            'documents' => [
                'name' => ['file1.pdf', 'file2.pdf'],
                'type' => ['application/pdf', 'application/pdf'],
                'tmp_name' => ['/tmp/phpYzdqkD', '/tmp/phpYzdqkE'],
                'error' => [0, 0],
                'size' => [12345, 67890],
                'full_path' => ['toto','titi']
            ],
            'titi' => [
                'name' => ['file1.pdf', 'file2.pdf'],
                'type' => ['application/pdf', 'application/pdf'],
                'tmp_name' => ['/tmp/phpYzdqkD', '/tmp/phpYzdqkE'],
                'error' => [0, 0],
                'size' => [12345, 67890],
                'full_path' => ['toto','titi']
            ],
        ];

        $expected = [
            'documents' => [
                [
                    'name' => 'file1.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkD',
                    'error' => 0,
                    'size' => 12345,
                    'full_path' => 'toto'
                ],
                [
                    'name' => 'file2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkE',
                    'error' => 0,
                    'size' => 67890,
                    'full_path' => 'titi'
                ],
            ],
             'titi' => [
                [
                    'name' => 'file1.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkD',
                    'error' => 0,
                    'size' => 12345,
                    'full_path' => 'toto'
                ],
                [
                    'name' => 'file2.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => '/tmp/phpYzdqkE',
                    'error' => 0,
                    'size' => 67890,
                    'full_path' => 'titi'
                ],
            ],
        ];

        $this->assertEquals($expected, FileFormator::convert($files));
    }
}