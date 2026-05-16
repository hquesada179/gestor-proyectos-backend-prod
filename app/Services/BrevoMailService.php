<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoMailService
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';

    public function send(string $toEmail, string $subject, string $htmlContent): bool
    {
        $apiKey = config('services.brevo.api_key');

        if (empty($apiKey)) {
            Log::error('BrevoMailService: BREVO_API_KEY no configurada.');
            return false;
        }

        $payload = [
            'sender' => [
                'name'  => config('mail.from.name', 'Scrumter'),
                'email' => config('mail.from.address', 'soporte@scrumter.io'),
            ],
            'to'          => [['email' => $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $htmlContent,
        ];

        try {
            $response = Http::withHeaders([
                'api-key'      => $apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(10)->post(self::API_URL, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error('BrevoMailService: error al enviar correo.', [
                'to'     => $toEmail,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('BrevoMailService: excepción al llamar API.', [
                'to'      => $toEmail,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
