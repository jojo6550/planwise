<?php
/**
 * SanitizeTest
 *
 * Unit tests for every function in helpers/sanitize.php.
 * No database connection required.
 */

use PHPUnit\Framework\TestCase;

class SanitizeTest extends TestCase
{
    // ---------------------------------------------------------------
    // sanitizeString
    // ---------------------------------------------------------------

    public function testSanitizeStringTrimsWhitespace(): void
    {
        $this->assertSame('hello', sanitizeString('  hello  '));
    }

    public function testSanitizeStringStripsBackslashes(): void
    {
        $this->assertSame("it's", sanitizeString("it\\'s"));
    }

    public function testSanitizeStringRemovesNullBytes(): void
    {
        $this->assertSame('hello', sanitizeString("hel\0lo"));
    }

    public function testSanitizeStringConvertsNullToEmptyString(): void
    {
        $this->assertSame('', sanitizeString(null));
    }

    public function testSanitizeStringConvertsIntToString(): void
    {
        $this->assertSame('42', sanitizeString(42));
    }

    // ---------------------------------------------------------------
    // sanitizeInt
    // ---------------------------------------------------------------

    public function testSanitizeIntConvertsStringToInt(): void
    {
        $this->assertSame(7, sanitizeInt('7'));
    }

    public function testSanitizeIntReturnsDefaultForNegative(): void
    {
        $this->assertSame(0, sanitizeInt(-5));
    }

    public function testSanitizeIntReturnsDefaultForNull(): void
    {
        $this->assertSame(0, sanitizeInt(null));
    }

    public function testSanitizeIntReturnsDefaultForEmptyString(): void
    {
        $this->assertSame(0, sanitizeInt(''));
    }

    public function testSanitizeIntUsesProvidedDefault(): void
    {
        $this->assertSame(1, sanitizeInt(null, 1));
    }

    public function testSanitizeIntTruncatesFloat(): void
    {
        $this->assertSame(3, sanitizeInt(3.9));
    }

    // ---------------------------------------------------------------
    // sanitizeFloat
    // ---------------------------------------------------------------

    public function testSanitizeFloatConvertsStringToFloat(): void
    {
        $this->assertSame(3.14, sanitizeFloat('3.14'));
    }

    public function testSanitizeFloatReturnsDefaultForNegative(): void
    {
        $this->assertSame(0.0, sanitizeFloat(-1.5));
    }

    public function testSanitizeFloatReturnsDefaultForNull(): void
    {
        $this->assertSame(0.0, sanitizeFloat(null));
    }

    // ---------------------------------------------------------------
    // sanitizeEmail
    // ---------------------------------------------------------------

    public function testSanitizeEmailLowercases(): void
    {
        $this->assertSame('user@example.com', sanitizeEmail('USER@EXAMPLE.COM'));
    }

    public function testSanitizeEmailTrims(): void
    {
        $this->assertSame('user@example.com', sanitizeEmail('  user@example.com  '));
    }

    public function testSanitizeEmailRemovesInvalidChars(): void
    {
        // filter_var FILTER_SANITIZE_EMAIL strips chars not allowed in emails
        $result = sanitizeEmail('us er@example.com');
        $this->assertStringNotContainsString(' ', $result);
    }

    // ---------------------------------------------------------------
    // sanitizeBool
    // ---------------------------------------------------------------

    public function testSanitizeBoolStringTrueReturnsTrue(): void
    {
        $this->assertTrue(sanitizeBool('true'));
    }

    public function testSanitizeBoolString1ReturnsTrue(): void
    {
        $this->assertTrue(sanitizeBool('1'));
    }

    public function testSanitizeBoolStringOnReturnsTrue(): void
    {
        $this->assertTrue(sanitizeBool('on'));
    }

    public function testSanitizeBoolStringFalseReturnsFalse(): void
    {
        $this->assertFalse(sanitizeBool('false'));
    }

    public function testSanitizeBoolString0ReturnsFalse(): void
    {
        $this->assertFalse(sanitizeBool('0'));
    }

    public function testSanitizeBoolStringOffReturnsFalse(): void
    {
        $this->assertFalse(sanitizeBool('off'));
    }

    public function testSanitizeBoolPassthroughBoolTrue(): void
    {
        $this->assertTrue(sanitizeBool(true));
    }

    public function testSanitizeBoolPassthroughBoolFalse(): void
    {
        $this->assertFalse(sanitizeBool(false));
    }

    // ---------------------------------------------------------------
    // sanitizeArray
    // ---------------------------------------------------------------

    public function testSanitizeArrayStringType(): void
    {
        $input  = ['a' => '  hello  ', 'b' => "it\\'s"];
        $result = sanitizeArray($input, 'string');
        $this->assertSame('hello', $result['a']);
        $this->assertSame("it's", $result['b']);
    }

    public function testSanitizeArrayIntType(): void
    {
        $input  = ['x' => '5', 'y' => '-3'];
        $result = sanitizeArray($input, 'int');
        $this->assertSame(5, $result['x']);
        $this->assertSame(0, $result['y']); // negative → default 0
    }

    public function testSanitizeArrayEmailType(): void
    {
        $input  = ['email' => 'USER@EXAMPLE.COM'];
        $result = sanitizeArray($input, 'email');
        $this->assertSame('user@example.com', $result['email']);
    }

    public function testSanitizeArrayBoolType(): void
    {
        $input  = ['flag' => 'true'];
        $result = sanitizeArray($input, 'bool');
        $this->assertTrue($result['flag']);
    }

    // ---------------------------------------------------------------
    // sanitizeLikePattern
    // ---------------------------------------------------------------

    public function testSanitizeLikePatternEscapesPercent(): void
    {
        $this->assertSame('\%100', sanitizeLikePattern('%100'));
    }

    public function testSanitizeLikePatternEscapesUnderscore(): void
    {
        $this->assertSame('hello\_world', sanitizeLikePattern('hello_world'));
    }

    public function testSanitizeLikePatternEscapesBackslash(): void
    {
        $this->assertSame('path\\\\to', sanitizeLikePattern('path\\to'));
    }

    public function testSanitizeLikePatternLeavesNormalStringAlone(): void
    {
        $this->assertSame('hello', sanitizeLikePattern('hello'));
    }

    // ---------------------------------------------------------------
    // sanitizeSearchQuery
    // ---------------------------------------------------------------

    public function testSanitizeSearchQueryRemovesDangerousChars(): void
    {
        $result = sanitizeSearchQuery('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function testSanitizeSearchQueryTrims(): void
    {
        $this->assertSame('hello', sanitizeSearchQuery('  hello  '));
    }

    public function testSanitizeSearchQueryLimitsTo255Chars(): void
    {
        $long   = str_repeat('a', 300);
        $result = sanitizeSearchQuery($long);
        $this->assertLessThanOrEqual(255, mb_strlen($result));
    }

    public function testSanitizeSearchQueryAllowsLettersDigitsSpacesHyphens(): void
    {
        $input  = 'Math grade-10 lesson, topic 2.5';
        $result = sanitizeSearchQuery($input);
        // Core content should survive
        $this->assertStringContainsString('Math', $result);
        $this->assertStringContainsString('grade', $result);
    }

    // ---------------------------------------------------------------
    // sanitizeUsername
    // ---------------------------------------------------------------

    public function testSanitizeUsernameValidReturnsUsername(): void
    {
        $this->assertSame('john_doe', sanitizeUsername('john_doe'));
    }

    public function testSanitizeUsernameTooShortReturnsNull(): void
    {
        $this->assertNull(sanitizeUsername('ab'));
    }

    public function testSanitizeUsernameTooLongReturnsNull(): void
    {
        $this->assertNull(sanitizeUsername(str_repeat('a', 31)));
    }

    public function testSanitizeUsernameWithSpacesReturnsNull(): void
    {
        $this->assertNull(sanitizeUsername('john doe'));
    }

    public function testSanitizeUsernameWithSpecialCharsReturnsNull(): void
    {
        $this->assertNull(sanitizeUsername('john@doe'));
    }

    // ---------------------------------------------------------------
    // sanitizeUrl
    // ---------------------------------------------------------------

    public function testSanitizeUrlValidHttpsReturnsUrl(): void
    {
        $this->assertSame('https://example.com', sanitizeUrl('https://example.com'));
    }

    public function testSanitizeUrlAddsMissingHttpProtocol(): void
    {
        $result = sanitizeUrl('example.com');
        $this->assertStringStartsWith('http://', $result);
    }

    public function testSanitizeUrlInvalidReturnsNull(): void
    {
        $this->assertNull(sanitizeUrl('not a url!!'));
    }

    // ---------------------------------------------------------------
    // escapeOutput
    // ---------------------------------------------------------------

    public function testEscapeOutputConvertsNullToEmptyString(): void
    {
        $this->assertSame('', escapeOutput(null));
    }

    public function testEscapeOutputEncodesHtmlTags(): void
    {
        $result = escapeOutput('<script>alert(1)</script>');
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $result);
    }

    public function testEscapeOutputEncodesDoubleQuotes(): void
    {
        $this->assertStringContainsString('&quot;', escapeOutput('"hello"'));
    }

    public function testEscapeOutputEncodesSingleQuotes(): void
    {
        $this->assertStringContainsString('&#039;', escapeOutput("it's"));
    }

    public function testEscapeOutputPassesSafeString(): void
    {
        $this->assertSame('Hello World', escapeOutput('Hello World'));
    }

    // ---------------------------------------------------------------
    // stripHtml
    // ---------------------------------------------------------------

    public function testStripHtmlRemovesAllTags(): void
    {
        $this->assertSame('Hello World', stripHtml('<p>Hello <b>World</b></p>'));
    }

    public function testStripHtmlTrimsWhitespace(): void
    {
        $this->assertSame('text', stripHtml('  text  '));
    }

    public function testStripHtmlLeavesPlainTextAlone(): void
    {
        $this->assertSame('plain text', stripHtml('plain text'));
    }
}
