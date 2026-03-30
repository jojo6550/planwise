<?php
/**
 * LessonPlanTest
 *
 * Unit tests for the LessonPlan class.
 *
 * Uses the same Reflection-based DB singleton injection strategy as UserTest
 * so no real database connection is required.
 */

use PHPUnit\Framework\TestCase;

class LessonPlanTest extends TestCase
{
    private $mockDb;
    private ReflectionProperty $dbInstanceProp;

    protected function setUp(): void
    {
        $this->mockDb = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ref = new ReflectionClass(Database::class);
        $this->dbInstanceProp = $ref->getProperty('instance');
        $this->dbInstanceProp->setAccessible(true);
        $this->dbInstanceProp->setValue(null, $this->mockDb);
    }

    protected function tearDown(): void
    {
        $this->dbInstanceProp->setValue(null, null);
    }

    // ---------------------------------------------------------------
    // create() — validation paths
    // ---------------------------------------------------------------

    public function testCreateFailsWhenUserIdMissing(): void
    {
        $lp     = new LessonPlan();
        $result = $lp->create(['user_id' => '', 'title' => 'My Plan']);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('user_id', $result['message']);
    }

    public function testCreateFailsWhenTitleMissing(): void
    {
        $lp     = new LessonPlan();
        $result = $lp->create(['user_id' => 1, 'title' => '']);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('title', $result['message']);
    }

    public function testCreateFailsWhenTitleTooShort(): void
    {
        $lp     = new LessonPlan();
        $result = $lp->create(['user_id' => 1, 'title' => 'AB']);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('3 characters', $result['message']);
    }

    public function testCreateFailsForNegativeDuration(): void
    {
        $lp     = new LessonPlan();
        $result = $lp->create(['user_id' => 1, 'title' => 'My Plan', 'duration' => -5]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('positive', $result['message']);
    }

    public function testCreateFailsForNonNumericDuration(): void
    {
        $lp     = new LessonPlan();
        $result = $lp->create(['user_id' => 1, 'title' => 'My Plan', 'duration' => 'abc']);
        $this->assertFalse($result['success']);
    }

    public function testCreateSucceedsWithValidData(): void
    {
        $this->mockDb->method('insert')->willReturn('7');

        $lp     = new LessonPlan();
        $result = $lp->create([
            'user_id'    => 1,
            'title'      => 'Introduction to Algebra',
            'subject'    => 'Mathematics',
            'duration'   => 45,
            'status'     => 'draft',
        ]);
        $this->assertTrue($result['success']);
        $this->assertSame('7', $result['lesson_id']);
    }

    public function testCreateSucceedsWithEmptyOptionalDuration(): void
    {
        $this->mockDb->method('insert')->willReturn('8');

        $lp     = new LessonPlan();
        $result = $lp->create([
            'user_id' => 1,
            'title'   => 'History Lesson',
            'duration' => '',   // empty string is acceptable (treated as null)
        ]);
        $this->assertTrue($result['success']);
    }

    // ---------------------------------------------------------------
    // update() — validation paths
    // ---------------------------------------------------------------

    public function testUpdateFailsWhenTitleTooShort(): void
    {
        // Simulate getById returning an existing plan
        $existing = [
            'lesson_id'  => 3,
            'user_id'    => 1,
            'title'      => 'Old Title',
            'subject'    => 'Science',
            'grade_level' => '8',
            'duration'   => 30,
            'objectives' => '',
            'materials'  => '',
            'procedures' => '',
            'assessment' => '',
            'notes'      => '',
            'status'     => 'draft',
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
        ];
        $stmt = $this->createConfiguredMock(\PDOStatement::class, ['fetch' => $existing]);
        $this->mockDb->method('query')->willReturn($stmt);
        $this->mockDb->method('fetch')->willReturn($existing);

        $lp     = new LessonPlan();
        $result = $lp->update(3, ['title' => 'AB'], 1);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('3 characters', $result['message']);
    }

    public function testUpdateFailsForNegativeDuration(): void
    {
        $existing = [
            'lesson_id'  => 3, 'user_id' => 1, 'title' => 'Old Title',
            'subject' => '', 'grade_level' => '', 'duration' => 30,
            'objectives' => '', 'materials' => '', 'procedures' => '',
            'assessment' => '', 'notes' => '', 'status' => 'draft',
            'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com',
        ];
        $stmt = $this->createConfiguredMock(\PDOStatement::class, ['fetch' => $existing]);
        $this->mockDb->method('query')->willReturn($stmt);
        $this->mockDb->method('fetch')->willReturn($existing);

        $lp     = new LessonPlan();
        $result = $lp->update(3, ['duration' => -10], 1);
        $this->assertFalse($result['success']);
    }

    public function testUpdateFailsWhenPlanNotFound(): void
    {
        // getById returns null (plan not found or wrong user)
        $stmt = $this->createConfiguredMock(\PDOStatement::class, ['fetch' => false]);
        $this->mockDb->method('query')->willReturn($stmt);
        $this->mockDb->method('fetch')->willReturn(false);

        $lp     = new LessonPlan();
        $result = $lp->update(999, ['title' => 'New Title'], 1);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    // ---------------------------------------------------------------
    // getStats()
    // ---------------------------------------------------------------

    public function testGetStatsReturnsCastIntsFromDbRow(): void
    {
        $row = ['total' => '10', 'published' => '4', 'drafts' => '3', 'archived' => '3'];
        $this->mockDb->method('fetch')->willReturn($row);

        $lp    = new LessonPlan();
        $stats = $lp->getStats(1);

        $this->assertSame(10, $stats['total']);
        $this->assertSame(4, $stats['published']);
        $this->assertSame(3, $stats['drafts']);
        $this->assertSame(3, $stats['archived']);
    }

    public function testGetStatsReturnsZerosWhenDbReturnsNothing(): void
    {
        $this->mockDb->method('fetch')->willReturn(false);

        $lp    = new LessonPlan();
        $stats = $lp->getStats(99);

        $this->assertSame(0, $stats['total']);
        $this->assertSame(0, $stats['published']);
        $this->assertSame(0, $stats['drafts']);
        $this->assertSame(0, $stats['archived']);
    }
}
