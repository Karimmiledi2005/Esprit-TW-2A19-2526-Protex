<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers/CsrfHelper.php';

final class CsrfHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testGenerateTokenCreatesTokenInSession(): void
    {
        self::assertEmpty($_SESSION['csrf_token'] ?? '');
        $token = CsrfHelper::generate();
        self::assertNotEmpty($token);
        self::assertSame($token, $_SESSION['csrf_token']);
    }

    public function testVerifyCorrectTokenReturnsTrue(): void
    {
        $token = CsrfHelper::generate();
        self::assertTrue(CsrfHelper::verify($token));
    }

    public function testVerifyIncorrectTokenReturnsFalse(): void
    {
        CsrfHelper::generate();
        self::assertFalse(CsrfHelper::verify('invalid_token'));
    }

    public function testFieldReturnsInputHtml(): void
    {
        $token = CsrfHelper::generate();
        $field = CsrfHelper::field();
        self::assertStringContainsString('type="hidden"', $field);
        self::assertStringContainsString('name="csrf_token"', $field);
        self::assertStringContainsString('value="' . $token . '"', $field);
    }

    public function testGetAndGenerateFieldAliasMethods(): void
    {
        $token = CsrfHelper::getToken();
        self::assertSame($token, $_SESSION['csrf_token']);

        $field = CsrfHelper::generateField();
        self::assertStringContainsString('value="' . $token . '"', $field);
    }
}
