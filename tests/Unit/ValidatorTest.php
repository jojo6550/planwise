<?php
/**
 * ValidatorTest
 *
 * Unit tests for the Validator class.
 * Covers every validation rule and all error-reporting helpers.
 * No database connection required.
 */

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        $this->v = new Validator();
    }

    // ---------------------------------------------------------------
    // required rule
    // ---------------------------------------------------------------

    public function testRequiredFailsOnEmptyString(): void
    {
        $result = $this->v->validate(['name' => ''], ['name' => 'required']);
        $this->assertFalse($result);
        $this->assertNotEmpty($this->v->getErrors('name'));
    }

    public function testRequiredFailsOnNull(): void
    {
        $result = $this->v->validate(['name' => null], ['name' => 'required']);
        $this->assertFalse($result);
    }

    public function testRequiredFailsWhenFieldMissing(): void
    {
        $result = $this->v->validate([], ['name' => 'required']);
        $this->assertFalse($result);
    }

    public function testRequiredPassesForStringZero(): void
    {
        $result = $this->v->validate(['count' => '0'], ['count' => 'required']);
        $this->assertTrue($result);
    }

    public function testRequiredPassesForIntegerZero(): void
    {
        $result = $this->v->validate(['count' => 0], ['count' => 'required']);
        $this->assertTrue($result);
    }

    public function testRequiredPassesForPopulatedString(): void
    {
        $result = $this->v->validate(['name' => 'Alice'], ['name' => 'required']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // email rule
    // ---------------------------------------------------------------

    public function testEmailFailsForInvalidFormat(): void
    {
        $result = $this->v->validate(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertFalse($result);
    }

    public function testEmailFailsForMissingAtSign(): void
    {
        $result = $this->v->validate(['email' => 'userdomain.com'], ['email' => 'email']);
        $this->assertFalse($result);
    }

    public function testEmailPassesForValidAddress(): void
    {
        $result = $this->v->validate(['email' => 'user@example.com'], ['email' => 'email']);
        $this->assertTrue($result);
    }

    public function testEmailPassesWhenEmpty(): void
    {
        // email is optional — an empty value should not trigger the email rule error
        $result = $this->v->validate(['email' => ''], ['email' => 'email']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // min rule
    // ---------------------------------------------------------------

    public function testMinFailsWhenShorterThanMin(): void
    {
        $result = $this->v->validate(['pass' => 'abc'], ['pass' => 'min:8']);
        $this->assertFalse($result);
        $this->assertStringContainsString('8', $this->v->getFirstError('pass'));
    }

    public function testMinPassesWhenExactlyMin(): void
    {
        $result = $this->v->validate(['pass' => 'abcdefgh'], ['pass' => 'min:8']);
        $this->assertTrue($result);
    }

    public function testMinPassesWhenLongerThanMin(): void
    {
        $result = $this->v->validate(['pass' => 'abcdefghi'], ['pass' => 'min:8']);
        $this->assertTrue($result);
    }

    public function testMinPassesWhenEmpty(): void
    {
        // min only fires when the value is non-empty
        $result = $this->v->validate(['pass' => ''], ['pass' => 'min:8']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // max rule
    // ---------------------------------------------------------------

    public function testMaxFailsWhenLongerThanMax(): void
    {
        $result = $this->v->validate(['name' => 'abcdefghijk'], ['name' => 'max:5']);
        $this->assertFalse($result);
    }

    public function testMaxPassesWhenExactlyMax(): void
    {
        $result = $this->v->validate(['name' => 'abcde'], ['name' => 'max:5']);
        $this->assertTrue($result);
    }

    public function testMaxPassesWhenShorterThanMax(): void
    {
        $result = $this->v->validate(['name' => 'abc'], ['name' => 'max:5']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // numeric rule
    // ---------------------------------------------------------------

    public function testNumericFailsForLetters(): void
    {
        $result = $this->v->validate(['age' => 'twenty'], ['age' => 'numeric']);
        $this->assertFalse($result);
    }

    public function testNumericPassesForIntegerString(): void
    {
        $result = $this->v->validate(['age' => '42'], ['age' => 'numeric']);
        $this->assertTrue($result);
    }

    public function testNumericPassesForFloatString(): void
    {
        $result = $this->v->validate(['price' => '9.99'], ['price' => 'numeric']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // url rule
    // ---------------------------------------------------------------

    public function testUrlFailsForInvalidUrl(): void
    {
        $result = $this->v->validate(['site' => 'not a url'], ['site' => 'url']);
        $this->assertFalse($result);
    }

    public function testUrlPassesForHttpsUrl(): void
    {
        $result = $this->v->validate(['site' => 'https://example.com'], ['site' => 'url']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // alpha rule
    // ---------------------------------------------------------------

    public function testAlphaFailsForAlphanumeric(): void
    {
        $result = $this->v->validate(['name' => 'John123'], ['name' => 'alpha']);
        $this->assertFalse($result);
    }

    public function testAlphaPassesForPureLetters(): void
    {
        $result = $this->v->validate(['name' => 'John'], ['name' => 'alpha']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // alphanum rule
    // ---------------------------------------------------------------

    public function testAlphanumFailsForSpecialChars(): void
    {
        $result = $this->v->validate(['user' => 'hello!'], ['user' => 'alphanum']);
        $this->assertFalse($result);
    }

    public function testAlphanumPassesForLettersAndDigits(): void
    {
        $result = $this->v->validate(['user' => 'Hello42'], ['user' => 'alphanum']);
        $this->assertTrue($result);
    }

    // ---------------------------------------------------------------
    // Chained / multi-rule
    // ---------------------------------------------------------------

    public function testPipeChainedRules(): void
    {
        // required|email|min:5 — empty value stops at required
        $result = $this->v->validate(['email' => ''], ['email' => 'required|email|min:5']);
        $this->assertFalse($result);
        // Only one error per field (stops on first failure)
        $this->assertCount(1, $this->v->getErrors('email'));
    }

    public function testPipeChainedRulesAsArray(): void
    {
        $result = $this->v->validate(
            ['email' => 'a@b.com'],
            ['email' => ['required', 'email', 'min:5']]
        );
        $this->assertTrue($result);
    }

    public function testMultipleFieldsIndependent(): void
    {
        $result = $this->v->validate(
            ['name' => '', 'email' => 'valid@example.com'],
            ['name' => 'required', 'email' => 'required|email']
        );
        $this->assertFalse($result);
        $this->assertTrue($this->v->hasErrors('name'));
        $this->assertFalse($this->v->hasErrors('email'));
    }

    // ---------------------------------------------------------------
    // Error-reporting helpers
    // ---------------------------------------------------------------

    public function testGetAllErrorsReturnsAllFields(): void
    {
        $this->v->validate(
            ['first' => '', 'email' => 'bad'],
            ['first' => 'required', 'email' => 'email']
        );
        $errors = $this->v->getAllErrors();
        $this->assertArrayHasKey('first', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testGetErrorsReturnsArrayForField(): void
    {
        $this->v->validate(['name' => ''], ['name' => 'required']);
        $errors = $this->v->getErrors('name');
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    public function testGetErrorsReturnsEmptyArrayForValidField(): void
    {
        $this->v->validate(['name' => 'Alice'], ['name' => 'required']);
        $this->assertEmpty($this->v->getErrors('name'));
    }

    public function testHasErrorsReturnsTrueWhenAnyError(): void
    {
        $this->v->validate(['name' => ''], ['name' => 'required']);
        $this->assertTrue($this->v->hasErrors());
    }

    public function testHasErrorsReturnsFalseWhenNoErrors(): void
    {
        $this->v->validate(['name' => 'Alice'], ['name' => 'required']);
        $this->assertFalse($this->v->hasErrors());
    }

    public function testHasErrorsWithFieldNameReturnsTrueForInvalidField(): void
    {
        $this->v->validate(['name' => ''], ['name' => 'required']);
        $this->assertTrue($this->v->hasErrors('name'));
    }

    public function testHasErrorsWithFieldNameReturnsFalseForValidField(): void
    {
        $this->v->validate(['name' => 'Alice'], ['name' => 'required']);
        $this->assertFalse($this->v->hasErrors('name'));
    }

    public function testGetFirstErrorReturnsFirstMessage(): void
    {
        $this->v->validate(['name' => ''], ['name' => 'required']);
        $first = $this->v->getFirstError('name');
        $this->assertIsString($first);
        $this->assertNotEmpty($first);
    }

    public function testGetFirstErrorReturnsNullWhenNoError(): void
    {
        $this->v->validate(['name' => 'Alice'], ['name' => 'required']);
        $this->assertNull($this->v->getFirstError('name'));
    }

    public function testValidateResetsErrorsBetweenCalls(): void
    {
        $this->v->validate(['name' => ''], ['name' => 'required']);
        $this->assertTrue($this->v->hasErrors());

        // Second call with valid data should clear errors
        $this->v->validate(['name' => 'Alice'], ['name' => 'required']);
        $this->assertFalse($this->v->hasErrors());
    }
}
