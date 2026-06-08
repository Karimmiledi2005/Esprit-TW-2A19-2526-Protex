<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Twilio\Rest\Client;

class SmsService
{
    /**
     * false = simulation sûre pour la démo.
     * true  = envoi réel avec Twilio après avoir mis les vrais identifiants.
     */
    private static bool $useRealSms = false;

    private static string $accountSid = 'TON_ACCOUNT_SID';
    private static string $authToken = 'TON_AUTH_TOKEN';
    private static string $twilioNumber = '+1234567890';

    public static function send(string $to, string $message): array
    {
        $to = trim($to);
        $message = trim($message);

        if ($to === '' || $message === '') {
            return [
                'success' => false,
                'mode' => 'simulation',
                'error' => 'Numéro ou message vide.'
            ];
        }

        $to = self::formatTunisianPhone($to);

        if (self::$useRealSms === false) {
            return [
                'success' => true,
                'mode' => 'simulation',
                'to' => $to,
                'message' => $message
            ];
        }

        try {
            $client = new Client(self::$accountSid, self::$authToken);

            $client->messages->create($to, [
                'from' => self::$twilioNumber,
                'body' => $message
            ]);

            return [
                'success' => true,
                'mode' => 'real',
                'to' => $to,
                'message' => $message
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'mode' => 'real',
                'to' => $to,
                'error' => $e->getMessage()
            ];
        }
    }

    private static function formatTunisianPhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '.', '(', ')'], '', $phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        if (str_starts_with($phone, '00216')) {
            return '+216' . substr($phone, 5);
        }

        if (str_starts_with($phone, '216')) {
            return '+' . $phone;
        }

        return '+216' . ltrim($phone, '0');
    }
}
