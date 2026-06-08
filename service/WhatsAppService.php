<?php

class WhatsAppService
{
    private static bool $useRealWhatsApp = false; // false = simulation, true = real send

    private static string $baseUrl = "https://2yxe86.api.infobip.com";
    private static string $apiKey = "9504774ecea9292f7e6856e2c01eca59-1e7abb85-a238-4885-a348-3a3a80fd8e07";
    private static string $sender = "447860088970";

    public static function sendText(string $to, string $message): array
    {
        $to = self::formatTunisianPhone($to);
        $message = trim($message);

        if ($to === '' || $message === '') {
            return [
                "success" => false,
                "mode" => "validation",
                "error" => "Numéro ou message vide."
            ];
        }

        if (!self::$useRealWhatsApp) {
            return [
                "success" => true,
                "mode" => "simulation",
                "channel" => "whatsapp",
                "to" => $to,
                "message" => $message
            ];
        }

        $url = self::$baseUrl . "/whatsapp/1/message/text";

        $payload = [
            "from" => self::$sender,
            "to" => $to,
            "content" => [
                "text" => $message
            ]
        ];

        return self::postJson($url, $payload);
    }

    private static function postJson(string $url, array $payload): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: App " . self::$apiKey,
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($response === false) {
            return [
                "success" => false,
                "error" => $error
            ];
        }

        return [
            "success" => $httpCode >= 200 && $httpCode < 300,
            "http_code" => $httpCode,
            "response" => json_decode($response, true) ?? $response
        ];
    }

    private static function formatTunisianPhone(string $phone): string
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