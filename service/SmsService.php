<?php

class SmsService
{
    private static function loadEnv(): void
    {
        // 1. Charger config.php (qui définit les constantes INFOBIP_*)
        if (!defined('INFOBIP_API_KEY') && file_exists(__DIR__ . '/../config.php')) {
            require_once __DIR__ . '/../config.php';
        }

        // 2. Propager constantes vers $_ENV (compat SmsService)
        if (defined('INFOBIP_API_KEY'))  $_ENV['INFOBIP_API_KEY']  = INFOBIP_API_KEY;
        if (defined('INFOBIP_BASE_URL')) $_ENV['INFOBIP_BASE_URL'] = INFOBIP_BASE_URL;
        if (defined('INFOBIP_SMS_SENDER')) $_ENV['INFOBIP_SMS_SENDER'] = INFOBIP_SMS_SENDER;

        // 3. Fallback .env si existe
        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath)) return;
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $_ENV[trim($key)] = trim($value);
        }
    }

    public static function send(string $to, string $message): array
    {
        return self::sendSms($to, $message);  //(numéro téléphone, contenu SMS)
    }

    public static function sendSms(string $to, string $message): array
    {
        self::loadEnv();

        $baseUrl = $_ENV['INFOBIP_BASE_URL'] ?? '';
        $apiKey = $_ENV['INFOBIP_API_KEY'] ?? '';
        $sender = $_ENV['INFOBIP_SMS_SENDER'] ?? 'ServiceSMS';

        $to = self::formatPhone($to);
        $message = trim($message);

        if ($baseUrl === '' || $apiKey === '' || $apiKey === 'votre_cle_infobip') {
            // Mock mode for development if key is placeholder
            if ($apiKey === 'votre_cle_infobip') {
                return [
                    "success" => true,
                    "http_code" => 200,
                    "error" => null,
                    "is_mock" => true,
                    "response" => [
                        "messages" => [
                            [
                                "to" => $to,
                                "status" => ["name" => "MOCK_SENT"],
                                "messageId" => "mock_" . uniqid()
                            ]
                        ]
                    ]
                ];
            }
            return [
                "success" => false,
                "http_code" => 0,
                "error" => "Configuration Infobip manquante dans .env.",
                "response" => null
            ];
        }

        if ($to === '' || $message === '') {
            return [
                "success" => false,
                "http_code" => 0,
                "error" => "Numéro ou message vide.",
                "response" => null
            ];
        }

        $url = rtrim($baseUrl, '/') . "/sms/3/messages";

        $payload = [
            "messages" => [
                [
                    "sender" => $sender,
                    "destinations" => [
                        ["to" => $to]
                    ],
                    "content" => [
                        "text" => $message
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: App " . $apiKey,
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($response === false) {
            return [
                "success" => false,
                "http_code" => $httpCode,
                "error" => $error,
                "response" => null
            ];
        }

        return [
            "success" => $httpCode >= 200 && $httpCode < 300,
            "http_code" => $httpCode,
            "error" => $error,
            "response" => json_decode($response, true) ?? $response
        ];
    }

    private static function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, "00216")) {
            return substr($phone, 2);
        }

        if (str_starts_with($phone, "216") && strlen($phone) === 11) {
            return $phone;
        }

        if (strlen($phone) === 8) {
            return "216" . $phone;
        }

        return $phone;
    }
}