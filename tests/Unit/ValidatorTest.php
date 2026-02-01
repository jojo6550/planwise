<?php
/**
 * Validator Class Unit Tests
 * Tests input validation functionality
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/Validator.php';

class ValidatorTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    /**
     * Test required field validation
     */
    public function testRequiredValidation()
    {
        // Test with empty value
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        
        $this->assertFalse($this->validator->validate($data, $rules));
        $this->assertTrue($this->validator->hasErrors('name'));

        // Test with valid value
        $data = ['name' => 'John'];
        $this->assertTrue($this->validator->validate($data, $rules));
        $this->assertFalse($this->validator->hasErrors('name'));
    }

    /**
     * Test email validation
     */
    public function testEmailValidation()
    {
        $rules = ['email' => 'email'];

        // Valid emails
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'first+last@company.org'
        ];

        foreach ($validEmails as $email) {
            $data = ['email' => $email];
            $this->assertTrue($this->validator->validate($data, $rules), "Email $email should be valid");
        }

        // Invalid emails
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
            'user name@example.com'
        ];

        foreach ($invalidEmails as $email) {
            $data = ['email' => $email];
            $this->assertFalse($this->validator->validate($data, $rules), "Email $email should be invalid");
            $this->assertTrue($this->validator->hasErrors('email'));
        }
    }

    /**
     * Test minimum length validation
     */
    public function testMinValidation()
    {
        $rules = ['password' => 'min:8'];

        // Too short
        $data = ['password' => 'abc123'];
        $this->assertFalse($this->validator->validate($data, $rules));

        // Exactly minimum
        $data = ['password' => '12345678'];
        $this->assertTrue($this->validator->validate($data, $rules));

        // Longer than minimum
        $data = ['password' => 'longpassword123'];
        $this->assertTrue($this->validator->validate($data, $rules));
    }

    /**
     * Test maximum length validation
     */
    public function testMaxValidation()
    {
        $rules = ['username' => 'max:20'];

        // Within limit
        $data = ['username' => 'user123'];
        $this->assertTrue($this->validator->validate($data, $rules));

        // Exactly at limit
        $data = ['username' => str_repeat('a', 20)];
        $this->assertTrue($this->validator->validate($data, $rules));

        // Over limit
        $data = ['username' => str_repeat('a', 21)];
        $this->assertFalse($this->validator->validate($data, $rules));
    }

    /**
     * Test numeric validation
     */
    public function testNumericValidation()
    {
        $rules = ['age' => 'numeric'];

        // Valid numbers
        $data = ['age' => '25'];
        $this->assertTrue($this->validator->validate($data, $rules));

        $data = ['age' => 25];
        $this->assertTrue($this->validator->validate($data, $rules));

        // Invalid
        $data = ['age' => 'twenty'];
        $this->assertFalse($this->validator->validate($data, $rules));
    }

    /**
     * Test alpha validation
     */
    public function testAlphaValidation()
    {
        $rules = ['name' => 'alpha'];

        // Valid
        $data = ['name' => 'John'];
        $this->assertTrue($this->validator->validate($data, $rules));

        // Invalid - contains numbers
        $data = ['name' => 'John123'];
        $this->assertFalse($this->validator->validate($data, $rules));

        // Invalid - contains special characters
        $data = ['name' => 'John-Doe'];
        $this->assertFalse($this->validator->validate($data, $rules));
    }

    /**
     * Test alphanumeric validation
     */
    public function testAlphanumValidation()
    {
        $rules = ['code' => 'alphanum'];

        // Valid
        $data = ['code' => 'ABC123'];
        $this->assertTrue($this->validator->validate($data, $rules));

        // Invalid - contains special characters
        $data = ['code' => 'ABC-123'];
        $this->assertFalse($this->validator->validate($data, $rules));
    }

    /**
     * Test multiple rules
     */
    public function testMultipleRules()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'securepass123',
            'age' => '25'
        ];

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'age' => 'numeric'
        ];

        $this->assertTrue($this->validator->validate($data, $rules));
        $this->assertFalse($this->validator->hasErrors());
    }

    /**
     * Test getting error messages
     */
    public function testGetErrors()
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'required|email'];

        $this->validator->validate($data, $rules);

        $errors = $this->validator->getErrors('email');
        $this->assertNotEmpty($errors);
        $this->assertIsArray($errors);

        $firstError = $this->validator->getFirstError('email');
        $this->assertIsString($firstError);
        $this->assertStringContainsString('email', $firstError);
    }

    /**
     * Test URL validation
     */
    public function testUrlValidation()
    {
        $rules = ['website' => 'url'];

        // Valid URLs
        $validUrls = [
            'https://example.com',
            'http://www.example.com',
            'https://sub.domain.example.com/path'
        ];

        foreach ($validUrls as $url) {
            $data = ['website' => $url];
            $this->assertTrue($this->validator->validate($data, $rules), "URL $url should be valid");
        }

        // Invalid URLs
        $data = ['website' => 'not a url'];
        $this->assertFalse($this->validator->validate($data, $rules));
    }
}
