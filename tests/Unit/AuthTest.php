<?php
/**
 * AuthTest
 *
 * Unit tests for the Auth class.
 * The User dependency is injected via the constructor, so we mock it with
 * PHPUnit's createMock() — no real database connection is needed.
 */

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    /**
     * Ensure an active PHP session exists before each test.
     *
     * Auth::__construct() calls session_start() only if the session is not yet
     * active. By starting it here (with cookies and cache-limiter disabled via
     * bootstrap.php ini_set calls), Auth skips its own session_start() and
     * session_regenerate_id() works without emitting any header-related warnings.
     */
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // @ silences any residual warning; bootstrap already configured
            // session.use_cookies=0 and session.cache_limiter=''
            @session_start();
        }
        $_SESSION = [];
    }

    /** Release the session so subsequent tests get a clean state. */
    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function makeAuth(?User $userMock = null): Auth
    {
        return new Auth($userMock ?? $this->createMock(User::class));
    }

    private function buildUserRow(array $overrides = []): array
    {
        return array_merge([
            'user_id'      => 1,
            'email'        => 'teacher@example.com',
            'first_name'   => 'Jane',
            'last_name'    => 'Doe',
            'role_id'      => 2,
            'status'       => 'active',
            'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // login()
    // ---------------------------------------------------------------

    public function testLoginFailsWhenEmailIsEmpty(): void
    {
        $auth   = $this->makeAuth();
        $result = $auth->login('', 'password');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('required', strtolower($result['message']));
    }

    public function testLoginFailsWhenPasswordIsEmpty(): void
    {
        $auth   = $this->makeAuth();
        $result = $auth->login('user@example.com', '');
        $this->assertFalse($result['success']);
    }

    public function testLoginFailsForInvalidEmailFormat(): void
    {
        $auth   = $this->makeAuth();
        $result = $auth->login('not-an-email', 'password');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('invalid email', strtolower($result['message']));
    }

    public function testLoginFailsWhenUserNotFound(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('findByEmail')->willReturn(null);

        $auth   = $this->makeAuth($userMock);
        $result = $auth->login('nobody@example.com', 'password');
        $this->assertFalse($result['success']);
    }

    public function testLoginFailsWhenUserIsInactive(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('findByEmail')->willReturn(
            $this->buildUserRow(['status' => 'inactive'])
        );

        $auth   = $this->makeAuth($userMock);
        $result = $auth->login('teacher@example.com', 'secret123');
        $this->assertFalse($result['success']);
    }

    public function testLoginFailsWhenPasswordIsWrong(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('findByEmail')->willReturn($this->buildUserRow());

        $auth   = $this->makeAuth($userMock);
        $result = $auth->login('teacher@example.com', 'wrong-password');
        $this->assertFalse($result['success']);
    }

    public function testLoginSucceedsWithCorrectCredentials(): void
    {
        $row      = $this->buildUserRow();
        $userMock = $this->createMock(User::class);
        $userMock->method('findByEmail')->willReturn($row);

        $auth   = $this->makeAuth($userMock);
        $result = $auth->login('teacher@example.com', 'secret123');

        $this->assertTrue($result['success']);
        $this->assertSame('Login successful', $result['message']);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame(1, $result['user']['user_id']);
    }

    public function testLoginSetsSessionOnSuccess(): void
    {
        $row      = $this->buildUserRow();
        $userMock = $this->createMock(User::class);
        $userMock->method('findByEmail')->willReturn($row);

        $auth = $this->makeAuth($userMock);
        $auth->login('teacher@example.com', 'secret123');

        $this->assertTrue($_SESSION['authenticated'] ?? false);
        $this->assertSame(1, $_SESSION['user_id'] ?? null);
    }

    // ---------------------------------------------------------------
    // logout()
    // ---------------------------------------------------------------

    public function testLogoutClearsSessionAndReturnsSuccess(): void
    {
        $_SESSION = ['authenticated' => true, 'user_id' => 1];

        $auth   = $this->makeAuth();
        $result = $auth->logout();

        $this->assertTrue($result['success']);
        $this->assertEmpty($_SESSION);
    }

    // ---------------------------------------------------------------
    // check()
    // ---------------------------------------------------------------

    public function testCheckReturnsFalseWhenSessionEmpty(): void
    {
        $auth = $this->makeAuth();
        $this->assertFalse($auth->check());
    }

    public function testCheckReturnsTrueWhenSessionIsValid(): void
    {
        $_SESSION = [
            'authenticated' => true,
            'user_id'       => 1,
            'login_time'    => time(),
        ];
        $auth = $this->makeAuth();
        $this->assertTrue($auth->check());
    }

    public function testCheckReturnsFalseWhenSessionIsExpired(): void
    {
        $_SESSION = [
            'authenticated' => true,
            'user_id'       => 1,
            'login_time'    => time() - (31 * 60), // 31 minutes ago → expired
        ];
        $auth = $this->makeAuth();
        $this->assertFalse($auth->check());
    }

    // ---------------------------------------------------------------
    // user()
    // ---------------------------------------------------------------

    public function testUserReturnsNullWhenNotAuthenticated(): void
    {
        $auth = $this->makeAuth();
        $this->assertNull($auth->user());
    }

    public function testUserReturnsArrayWhenAuthenticated(): void
    {
        $_SESSION = [
            'authenticated' => true,
            'user_id'       => 1,
            'email'         => 'teacher@example.com',
            'first_name'    => 'Jane',
            'last_name'     => 'Doe',
            'role_id'       => 2,
            'login_time'    => time(),
        ];
        $auth = $this->makeAuth();
        $user = $auth->user();
        $this->assertIsArray($user);
        $this->assertSame(1, $user['user_id']);
        $this->assertSame('teacher@example.com', $user['email']);
    }

    // ---------------------------------------------------------------
    // hasRole()
    // ---------------------------------------------------------------

    public function testHasRoleReturnsFalseWhenNotAuthenticated(): void
    {
        $auth = $this->makeAuth();
        $this->assertFalse($auth->hasRole(2));
    }

    public function testHasRoleReturnsFalseForWrongRole(): void
    {
        $_SESSION = [
            'authenticated' => true,
            'user_id'       => 1,
            'role_id'       => 2,
            'login_time'    => time(),
        ];
        $auth = $this->makeAuth();
        $this->assertFalse($auth->hasRole(1)); // checking admin role when user is teacher
    }

    public function testHasRoleReturnsTrueForCorrectRole(): void
    {
        $_SESSION = [
            'authenticated' => true,
            'user_id'       => 1,
            'role_id'       => 2,
            'login_time'    => time(),
        ];
        $auth = $this->makeAuth();
        $this->assertTrue($auth->hasRole(2));
    }

    // ---------------------------------------------------------------
    // id()
    // ---------------------------------------------------------------

    public function testIdReturnsNullWhenNoSession(): void
    {
        $auth = $this->makeAuth();
        $this->assertNull($auth->id());
    }

    public function testIdReturnsUserIdWhenSessionSet(): void
    {
        $_SESSION['user_id'] = 42;
        $auth = $this->makeAuth();
        $this->assertSame(42, $auth->id());
    }
}
