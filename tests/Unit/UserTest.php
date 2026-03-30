<?php
/**
 * UserTest
 *
 * Unit tests for the User class.
 *
 * Because User::__construct() calls Database::getInstance() (singleton with a
 * private constructor), we inject a PHPUnit mock into the static $instance
 * property via Reflection, bypassing the real constructor.
 * The property is reset to null in tearDown() so tests remain isolated.
 */

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private $mockDb;
    private ReflectionProperty $dbInstanceProp;

    protected function setUp(): void
    {
        // Build a mock of Database without calling its private constructor
        $this->mockDb = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Inject the mock into the singleton slot
        $ref = new ReflectionClass(Database::class);
        $this->dbInstanceProp = $ref->getProperty('instance');
        $this->dbInstanceProp->setAccessible(true);
        $this->dbInstanceProp->setValue(null, $this->mockDb);
    }

    protected function tearDown(): void
    {
        // Reset the singleton so other tests get a clean state
        $this->dbInstanceProp->setValue(null, null);
    }

    // ---------------------------------------------------------------
    // create() — validation paths (no real DB call needed)
    // ---------------------------------------------------------------

    public function testCreateFailsWhenFirstNameMissing(): void
    {
        $user   = new User();
        $result = $user->create([
            'first_name' => '',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
            'password'   => 'secret123',
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('first_name', $result['message']);
    }

    public function testCreateFailsWhenLastNameMissing(): void
    {
        $user   = new User();
        $result = $user->create([
            'first_name' => 'Jane',
            'last_name'  => '',
            'email'      => 'jane@example.com',
            'password'   => 'secret123',
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('last_name', $result['message']);
    }

    public function testCreateFailsWhenEmailMissing(): void
    {
        $user   = new User();
        $result = $user->create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => '',
            'password'   => 'secret123',
        ]);
        $this->assertFalse($result['success']);
    }

    public function testCreateFailsForInvalidEmailFormat(): void
    {
        $user   = new User();
        $result = $user->create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'not-an-email',
            'password'   => 'secret123',
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid email', $result['message']);
    }

    public function testCreateFailsWhenEmailAlreadyExists(): void
    {
        // findByEmail query returns an existing user row
        $this->mockDb->method('query')
            ->willReturn($this->createConfiguredMock(\PDOStatement::class, [
                'fetch' => [
                    'user_id' => 99,
                    'email'   => 'jane@example.com',
                    'status'  => 'active',
                ],
            ]));

        $user   = new User();
        $result = $user->create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
            'password'   => 'secret123',
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['message']);
    }

    public function testCreateSucceedsWithValidData(): void
    {
        // First call (findByEmail) returns no existing user; second call (INSERT) is fine
        $stmtNoRow = $this->createConfiguredMock(\PDOStatement::class, [
            'fetch' => false,
        ]);
        $this->mockDb->method('query')->willReturn($stmtNoRow);
        $this->mockDb->method('insert')->willReturn('5'); // lastInsertId

        $user   = new User();
        $result = $user->create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
            'password'   => 'secret123',
        ]);
        $this->assertTrue($result['success']);
        $this->assertSame('5', $result['user_id']);
    }

    // ---------------------------------------------------------------
    // findByEmail()
    // ---------------------------------------------------------------

    public function testFindByEmailReturnsUserRowWhenFound(): void
    {
        $row  = ['user_id' => 1, 'email' => 'jane@example.com', 'status' => 'active'];
        $stmt = $this->createConfiguredMock(\PDOStatement::class, ['fetch' => $row]);
        $this->mockDb->method('query')->willReturn($stmt);

        $user   = new User();
        $result = $user->findByEmail('jane@example.com');
        $this->assertIsArray($result);
        $this->assertSame('jane@example.com', $result['email']);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createConfiguredMock(\PDOStatement::class, ['fetch' => false]);
        $this->mockDb->method('query')->willReturn($stmt);

        $user   = new User();
        $result = $user->findByEmail('nobody@example.com');
        $this->assertNull($result);
    }

    // ---------------------------------------------------------------
    // findById()
    // ---------------------------------------------------------------

    public function testFindByIdReturnsUserRowWhenFound(): void
    {
        $row  = ['user_id' => 1, 'email' => 'jane@example.com', 'role_name' => 'Teacher'];
        $stmt = $this->createConfiguredMock(\PDOStatement::class, ['fetch' => $row]);
        $this->mockDb->method('query')->willReturn($stmt);
        $this->mockDb->method('fetch')->willReturn($row);

        $user   = new User();
        $result = $user->findById(1);
        $this->assertIsArray($result);
        $this->assertSame(1, $result['user_id']);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->mockDb->method('fetch')->willReturn(false);

        $user   = new User();
        $result = $user->findById(999);
        $this->assertNull($result);
    }

    // ---------------------------------------------------------------
    // updateStatus()
    // ---------------------------------------------------------------

    public function testUpdateStatusFailsForInvalidStatus(): void
    {
        $user   = new User();
        $result = $user->updateStatus(1, 'banned');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid status', $result['message']);
    }

    public function testUpdateStatusSucceedsForActive(): void
    {
        $this->mockDb->method('update')->willReturn(1);

        $user   = new User();
        $result = $user->updateStatus(1, 'active');
        $this->assertTrue($result['success']);
    }

    public function testUpdateStatusSucceedsForInactive(): void
    {
        $this->mockDb->method('update')->willReturn(1);

        $user   = new User();
        $result = $user->updateStatus(1, 'inactive');
        $this->assertTrue($result['success']);
    }
}
