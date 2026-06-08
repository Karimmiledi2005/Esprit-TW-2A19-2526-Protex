<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../controller/FraudeService.php';

final class FraudeScoreTest extends TestCase
{
    public function testVagueDescriptionWithoutPhotoScoresHigh(): void
    {
        $service = $this->makeService();
        $result = $service->previewFraudScore('dégât des eaux', 'Problème urgent, rien à dire, sans détail');

        self::assertGreaterThan(60, $result['score']);
    }

    public function testClearDescriptionWithPhotoScoresLow(): void
    {
        $service = $this->makeService();
        $result = $service->previewFraudScore('accident auto', 'Grave accident et collision à 18h20 sur l’avenue. Mon véhicule (voiture) a eu un choc sur le par-choc et le phare. Deux témoins.', __FILE__);

        self::assertLessThan(40, $result['score']);
    }

    private function makeService(): FraudeService
    {
        $db = $this->createMock(PDO::class);
        return new FraudeService($db);
    }
}