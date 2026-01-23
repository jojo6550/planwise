<?php
/**
 * LessonSection Unit Tests
 * Tests CRUD operations for LessonSection class
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/LessonSection.php';
require_once __DIR__ . '/../classes/LessonPlan.php';
require_once __DIR__ . '/../classes/Database.php';

class LessonSectionTest extends TestCase
{
    private $lessonSection;
    private $lessonPlan;
    private $testUserId;
    private $testLessonPlanId;
    private $testSectionId;

    protected function setUp(): void
    {
        $this->lessonSection = new LessonSection();
        $this->lessonPlan = new LessonPlan();

        // Create test user if not exists
        $db = Database::getInstance();
        $result = $db->fetch("SELECT user_id FROM users WHERE email = 'test@example.com'");
        if (!$result) {
            $db->insert("INSERT INTO users (first_name, last_name, email, password_hash, role_id) VALUES ('Test', 'User', 'test@example.com', 'hash', 2)");
            $result = $db->fetch("SELECT user_id FROM users WHERE email = 'test@example.com'");
        }
        $this->testUserId = $result['user_id'];

        // Create test lesson plan
        $planData = [
            'user_id' => $this->testUserId,
            'title' => 'Test Lesson Plan for Sections',
        ];
        $planResult = $this->lessonPlan->create($planData);
        $this->testLessonPlanId = $planResult['lesson_plan_id'];
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $db = Database::getInstance();
        if ($this->testSectionId) {
            $db->delete("DELETE FROM lesson_sections WHERE section_id = ?", [$this->testSectionId]);
        }
        if ($this->testLessonPlanId) {
            $db->delete("DELETE FROM lesson_plans WHERE lesson_plan_id = ?", [$this->testLessonPlanId]);
        }
    }

    public function testCreateSection()
    {
        $data = [
            'lesson_plan_id' => $this->testLessonPlanId,
            'section_type' => 'introduction',
            'title' => 'Introduction Section',
            'content' => 'Welcome to the lesson',
            'duration' => 10,
            'order_position' => 1
        ];

        $result = $this->lessonSection->create($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('section_id', $result);
        $this->testSectionId = $result['section_id'];
    }

    public function testGetByLessonPlan()
    {
        // Create a section first
        $data = [
            'lesson_plan_id' => $this->testLessonPlanId,
            'section_type' => 'main_activity',
            'title' => 'Main Activity',
            'content' => 'Main content',
            'duration' => 30,
            'order_position' => 2
        ];
        $createResult = $this->lessonSection->create($data);
        $this->testSectionId = $createResult['section_id'];

        $sections = $this->lessonSection->getByLessonPlan($this->testLessonPlanId);

        $this->assertNotEmpty($sections);
        $this->assertEquals('Main Activity', $sections[0]['title']);
    }

    public function testUpdateSection()
    {
        // Create a section first
        $data = [
            'lesson_plan_id' => $this->testLessonPlanId,
            'section_type' => 'conclusion',
            'title' => 'Original Conclusion',
            'content' => 'Original content',
            'duration' => 5,
            'order_position' => 3
        ];
        $createResult = $this->lessonSection->create($data);
        $this->testSectionId = $createResult['section_id'];

        $updateData = [
            'section_type' => 'assessment',
            'title' => 'Updated Assessment',
            'content' => 'Updated content',
            'duration' => 15,
            'order_position' => 4
        ];

        $result = $this->lessonSection->update($this->testSectionId, $updateData);

        $this->assertTrue($result['success']);

        // Verify update
        $sections = $this->lessonSection->getByLessonPlan($this->testLessonPlanId);
        $updatedSection = array_filter($sections, fn($s) => $s['section_id'] == $this->testSectionId);
        $updatedSection = reset($updatedSection);

        $this->assertEquals('assessment', $updatedSection['section_type']);
        $this->assertEquals('Updated Assessment', $updatedSection['title']);
    }

    public function testDeleteSection()
    {
        // Create a section first
        $data = [
            'lesson_plan_id' => $this->testLessonPlanId,
            'section_type' => 'introduction',
            'title' => 'Section to Delete',
            'content' => 'Content',
            'duration' => 10,
            'order_position' => 1
        ];
        $createResult = $this->lessonSection->create($data);
        $this->testSectionId = $createResult['section_id'];

        $result = $this->lessonSection->delete($this->testSectionId);

        $this->assertTrue($result['success']);

        // Verify deletion
        $sections = $this->lessonSection->getByLessonPlan($this->testLessonPlanId);
        $deletedSection = array_filter($sections, fn($s) => $s['section_id'] == $this->testSectionId);
        $this->assertEmpty($deletedSection);

        // Prevent tearDown from trying to delete again
        $this->testSectionId = null;
    }

    public function testCascadeDeleteOnLessonPlanDelete()
    {
        // Create a section
        $data = [
            'lesson_plan_id' => $this->testLessonPlanId,
            'section_type' => 'introduction',
            'title' => 'Cascade Test Section',
            'content' => 'Content',
            'duration' => 10,
            'order_position' => 1
        ];
        $createResult = $this->lessonSection->create($data);
        $sectionId = $createResult['section_id'];

        // Delete the lesson plan
        $this->lessonPlan->delete($this->testLessonPlanId, $this->testUserId);

        // Check if section is also deleted (cascade)
        $sections = $this->lessonSection->getByLessonPlan($this->testLessonPlanId);
        $this->assertEmpty($sections);

        // Prevent tearDown from trying to delete
        $this->testLessonPlanId = null;
        $this->testSectionId = null;
    }
}
