<?php
/**
 * Auth Class Unit Tests
 * Tests authentication functionality
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Database.php';

/**
 * @runInSeparateProcess
 */
class AuthTest extends TestCase
{
    private $auth;
    private $userMock;

    protected function setUp(): void
    {
        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Create mock for User
        $this->userMock = $this->createMock(User::class);

        $this->auth = new Auth($this->userMock);
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
    }

    /**
     * Test successful login with valid credentials
     */
    public function testLoginWithValidCredentials()
    {
        // Arrange
        $email = 'josiah.johnson6550@gmail.com';
        $password = 'coriander6550'; // Assuming this is the test password

        $userData = [
            'user_id' => 1,
            'first_name' => 'Josiah',
            'last_name' => 'Johnson',
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => 2,
            'status' => 'active'
        ];

        $this->userMock->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($userData);

        // Act
        $result = $this->auth->login($email, $password);

        // Assert
        $this->assertTrue($result['success'], 'Login should succeed with valid credentials');
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($email, $result['user']['email']);
    }

    /**
     * Test login fails with invalid email
     */
    public function testLoginWithInvalidEmail()
    {
        // Arrange
        $email = 'nonexistent@example.com';
        $password = 'password123';

        $this->userMock->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        // Act
        $result = $this->auth->login($email, $password);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid email or password', $result['message']);
    }

    /**
     * Test login fails with invalid password
     */
    public function testLoginWithInvalidPassword()
    {
        // Arrange
        $email = 'josiah.johnson6550@gmail.com';
        $password = 'wrongpassword';

        // Act
        $result = $this->auth->login($email, $password);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid email or password', $result['message']);
    }

    /**
     * Test login with empty credentials
     */
    public function testLoginWithEmptyCredentials()
    {
        // Test empty email
        $result = $this->auth->login('', 'password123');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('required', $result['message']);

        // Test empty password
        $result = $this->auth->login('test@example.com', '');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('required', $result['message']);
    }

    /**
     * Test login with invalid email format
     */
    public function testLoginWithInvalidEmailFormat()
    {
        // Arrange
        $email = 'notanemail';
        $password = 'password123';

        // Act
        $result = $this->auth->login($email, $password);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid email format', $result['message']);
    }

    /**
     * Test authentication check
     */
    public function testCheck()
    {
        // Test not authenticated
        $this->assertFalse($this->auth->check());

        // Simulate authenticated session
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['login_time'] = time();

        $this->assertTrue($this->auth->check());
    }

    /**
     * Test session timeout
     */
    public function testSessionTimeout()
    {
        // Simulate expired session (over 30 minutes)
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['login_time'] = time() - (31 * 60); // 31 minutes ago

        // Check should fail and clean up session
        $this->assertFalse($this->auth->check());
    }

    /**
     * Test get user ID
     */
    public function testGetUserId()
    {
        // Not authenticated
        $this->assertNull($this->auth->id());

        // Authenticated
        $_SESSION['user_id'] = 42;
        $this->assertEquals(42, $this->auth->id());
    }

    /**
     * Test role checking
     */
    public function testHasRole()
    {
        // Not authenticated
        $this->assertFalse($this->auth->hasRole(1));

        // Authenticated with role
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['role_id'] = 2;
        $_SESSION['login_time'] = time();

        $this->assertTrue($this->auth->hasRole(2));
        $this->assertFalse($this->auth->hasRole(1));
    }

    /**
     * Test logout
     */
    public function testLogout()
    {
        // Set up authenticated session
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = 1;
        $_SESSION['email'] = 'test@example.com';

        // Logout
        $result = $this->auth->logout();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEmpty($_SESSION);
    }
}
