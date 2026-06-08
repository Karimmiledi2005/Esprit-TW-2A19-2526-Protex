<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../controller/AuthController.php';

final class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testEmptyEmailReturnsErrorString(): void
    {
        self::assertSame('Email et mot de passe requis', AuthController::validateLoginInput('', 'Demo@2025'));
    }

    public function testBlockedUserCannotLogin(): void
    {
        self::assertFalse(AuthController::canLogin(['statut' => 'bloque']));
    }
}