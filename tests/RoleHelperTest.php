<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers/RoleHelper.php';

final class RoleHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testGetRoleAndUserId(): void
    {
        $_SESSION['role'] = 'superadmin';
        $_SESSION['user_id'] = 42;

        self::assertSame('superadmin', RoleHelper::getRole());
        self::assertSame(42, RoleHelper::getUserId());
    }

    public function testSuperAdminAndClientChecks(): void
    {
        $_SESSION['role'] = 'superadmin';

        self::assertTrue(RoleHelper::isSuperAdmin());
        self::assertFalse(RoleHelper::isClient());
    }

    public function testCanDeleteSinistreForClientIsFalse(): void
    {
        $_SESSION['role'] = 'client';

        self::assertFalse(RoleHelper::canDeleteSinistre());
    }

    public function testIsAgentAndIsAdminAgence(): void
    {
        $_SESSION['role'] = 'agent';
        self::assertTrue(RoleHelper::isAgent());
        self::assertFalse(RoleHelper::isAdminAgence());

        $_SESSION['role'] = 'admin';
        self::assertTrue(RoleHelper::isAdminAgence());
        self::assertFalse(RoleHelper::isAgent());
    }

    public function testCanSeeFraudScore(): void
    {
        $_SESSION['role'] = 'client';
        self::assertFalse(RoleHelper::canSeeFraudScore());
        self::assertFalse(RoleHelper::canSeeFraudScoreGlobal());

        $_SESSION['role'] = 'agent';
        self::assertTrue(RoleHelper::canSeeFraudScore());
        self::assertFalse(RoleHelper::canSeeFraudScoreGlobal());

        $_SESSION['role'] = 'superadmin';
        self::assertTrue(RoleHelper::canSeeFraudScore());
        self::assertTrue(RoleHelper::canSeeFraudScoreGlobal());
    }

    public function testCanManagePermissions(): void
    {
        $_SESSION['role'] = 'agent';
        self::assertTrue(RoleHelper::canManageUsers());
        self::assertTrue(RoleHelper::canManageDevis());
        self::assertTrue(RoleHelper::canManageContrats());
        self::assertTrue(RoleHelper::canManageOffres());
        self::assertTrue(RoleHelper::canManagePaiements());
        self::assertTrue(RoleHelper::canManageAgences());
        self::assertTrue(RoleHelper::canManagePostes());

        $_SESSION['role'] = 'client';
        self::assertFalse(RoleHelper::canManageUsers());
    }
}