<?php
/**
 * File Class Unit Tests
 * Tests file upload and management functionality
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/File.php';
require_once __DIR__ . '/../../classes/Database.php';

class FileTest extends TestCase
{
    /**
     * Test allowed file types validation
     */
    public function testAllowedFileTypes()
    {
        $file = new File();
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($file);
        $method = $reflection->getMethod('isAllowedFileType');
        $method->setAccessible(true);

        // Test allowed types
        $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'png'];
        foreach ($allowedTypes as $type) {
            $result = $method->invoke($file, $type);
            $this->assertTrue($result, "File type $type should be allowed");
        }

        // Test disallowed types
        $disallowedTypes = ['exe', 'bat', 'php', 'sh'];
        foreach ($disallowedTypes as $type) {
            $result = $method->invoke($file, $type);
            $this->assertFalse($result, "File type $type should not be allowed");
        }
    }

    /**
     * Test file size validation
     */
    public function testFileSizeValidation()
    {
        $file = new File();
        
        $reflection = new ReflectionClass($file);
        $method = $reflection->getMethod('isValidFileSize');
        $method->setAccessible(true);

        // Test within limit (5MB default)
        $result = $method->invoke($file, 4 * 1024 * 1024); // 4MB
        $this->assertTrue($result);

        // Test over limit
        $result = $method->invoke($file, 10 * 1024 * 1024); // 10MB
        $this->assertFalse($result);
    }

    /**
     * Test file extension extraction
     */
    public function testGetFileExtension()
    {
        $file = new File();
        
        $reflection = new ReflectionClass($file);
        $method = $reflection->getMethod('getFileExtension');
        $method->setAccessible(true);

        $tests = [
            'document.pdf' => 'pdf',
            'image.JPG' => 'jpg',
            'file.name.with.dots.docx' => 'docx',
            'noextension' => ''
        ];

        foreach ($tests as $filename => $expected) {
            $result = $method->invoke($file, $filename);
            $this->assertEquals($expected, $result, "Extension for $filename should be $expected");
        }
    }

    /**
     * Test thumbnail generation would create proper dimensions
     */
    public function testThumbnailDimensions()
    {
        // This is a logic test for dimensions calculation
        $maxWidth = 200;
        $maxHeight = 200;

        // Test landscape image
        $origWidth = 800;
        $origHeight = 600;
        $ratio = $origWidth / $origHeight;

        if ($origWidth > $origHeight) {
            $thumbWidth = $maxWidth;
            $thumbHeight = (int)($maxWidth / $ratio);
        } else {
            $thumbHeight = $maxHeight;
            $thumbWidth = (int)($maxHeight * $ratio);
        }

        $this->assertEquals(200, $thumbWidth);
        $this->assertEquals(150, $thumbHeight);

        // Test portrait image
        $origWidth = 600;
        $origHeight = 800;
        $ratio = $origWidth / $origHeight;

        if ($origWidth > $origHeight) {
            $thumbWidth = $maxWidth;
            $thumbHeight = (int)($maxWidth / $ratio);
        } else {
            $thumbHeight = $maxHeight;
            $thumbWidth = (int)($maxHeight * $ratio);
        }

        $this->assertEquals(150, $thumbWidth);
        $this->assertEquals(200, $thumbHeight);
    }
}
