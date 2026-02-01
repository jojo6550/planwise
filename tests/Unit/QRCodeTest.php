<?php
/**
 * QRCode Class Unit Tests
 * Tests QR code generation functionality
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/QRCode.php';
require_once __DIR__ . '/../../classes/Database.php';

class QRCodeTest extends TestCase
{
    private $qrCode;
    private $testLessonId = 999; // Use a test ID that won't conflict

    protected function setUp(): void
    {
        $this->qrCode = new QRCode();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $qrDir = __DIR__ . '/../../public/qr/';
        if (is_dir($qrDir)) {
            $files = glob($qrDir . 'qr_' . $this->testLessonId . '_*.png');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Test QR code generation creates file
     */
    public function testGenerateCreatesFile()
    {
        // Act
        $result = $this->qrCode->generate($this->testLessonId);

        // Assert
        $this->assertTrue($result['success'], 'QR generation should succeed');
        $this->assertArrayHasKey('qr_image_path', $result);
        $this->assertFileExists($result['qr_image_path'], 'QR image file should exist');
        
        // Verify it's a valid PNG
        $imageInfo = getimagesize($result['qr_image_path']);
        $this->assertNotFalse($imageInfo, 'Should be a valid image');
        $this->assertEquals('image/png', $imageInfo['mime'], 'Should be PNG format');
    }

    /**
     * Test QR code data contains correct URL
     */
    public function testQRCodeDataContainsUrl()
    {
        // Act
        $result = $this->qrCode->generate($this->testLessonId);

        // Assert
        $this->assertArrayHasKey('qr_data', $result);
        $this->assertStringContainsString('/planwise/public/index.php', $result['qr_data']);
        $this->assertStringContainsString('page=teacher/lesson-plans/view', $result['qr_data']);
        $this->assertStringContainsString('id=' . $this->testLessonId, $result['qr_data']);
    }

    /**
     * Test QR directory is created if not exists
     */
    public function testQRDirectoryCreation()
    {
        $qrDir = __DIR__ . '/../../public/qr/';
        
        // Directory should exist after generation
        $result = $this->qrCode->generate($this->testLessonId);
        $this->assertTrue($result['success']);
        $this->assertDirectoryExists($qrDir);
    }

    /**
     * Test invalid lesson ID
     */
    public function testGenerateWithZeroLessonId()
    {
        // QR generation should still work but the link would be invalid
        // The class doesn't validate lesson existence
        $result = $this->qrCode->generate(0);
        $this->assertTrue($result['success']);
    }

    /**
     * Test regenerating QR code for same lesson
     */
    public function testRegenerateQRCode()
    {
        // Generate first QR
        $result1 = $this->qrCode->generate($this->testLessonId);
        $this->assertTrue($result1['success']);
        $path1 = $result1['qr_image_path'];

        // Wait a moment to ensure different timestamp
        sleep(1);

        // Generate again
        $result2 = $this->qrCode->generate($this->testLessonId);
        $this->assertTrue($result2['success']);
        $path2 = $result2['qr_image_path'];

        // Paths should be different (different timestamps)
        $this->assertNotEquals($path1, $path2);
    }
}
