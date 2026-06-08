<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../helpers/AuditLogger.php';

final class AuditLoggerTest extends TestCase
{
    private PDO $db;
    private string $testAction = 'unit_test_action';
    private string $testCible = 'unit_test_cible';
    private string $testDetails = 'unit_test_details_xyz';

    protected function setUp(): void
    {
        $this->db = config::getConnexion();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Clean up test entries from audit_log table
        $stmt = $this->db->prepare("DELETE FROM audit_log WHERE action = ?");
        $stmt->execute([$this->testAction]);
    }

    public function testAuditLoggerWritesToDatabase(): void
    {
        // Set user in session for logging
        $_SESSION['user_id'] = 9999; 
        
        // Log the action
        AuditLogger::log($this->testAction, $this->testCible, $this->testDetails);

        // Fetch the logged action from the database
        $stmt = $this->db->prepare("SELECT * FROM audit_log WHERE action = :action AND cible = :cible AND details = :details ORDER BY id DESC LIMIT 1");
        $stmt->execute([
            ':action' => $this->testAction,
            ':cible' => $this->testCible,
            ':details' => $this->testDetails
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($row, "The audit log row should be found in the database");
        self::assertSame($this->testAction, $row['action']);
        self::assertSame($this->testCible, $row['cible']);
        self::assertSame($this->testDetails, $row['details']);
    }

    public function testAuditLoggerWorksWithoutSessionUser(): void
    {
        // Ensure no user session is set
        unset($_SESSION['user_id']);
        unset($_SESSION['id_user']);

        AuditLogger::log($this->testAction, $this->testCible, $this->testDetails);

        $stmt = $this->db->prepare("SELECT * FROM audit_log WHERE action = :action AND cible = :cible AND details = :details ORDER BY id DESC LIMIT 1");
        $stmt->execute([
            ':action' => $this->testAction,
            ':cible' => $this->testCible,
            ':details' => $this->testDetails
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        self::assertNotFalse($row, "The audit log row should be found in the database");
        self::assertNull($row['id_user'], "The user ID should be logged as null when user is not logged in");
    }
}
