<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integración con la API de SMS transaccional de Brevo (antes Sendinblue).
 *
 * Responsabilidad única: enviar un SMS a un número de teléfono. No sabe
 * nada de citas ni de notificaciones internas; eso lo orquesta
 * NotificationService. Toda la configuración (api key, remitente, etc.)
 * vive en config/services.php -> 'brevo', que a su vez lee del .env.
 *
 * Documentación oficial:
 *   https://developers.brevo.com/reference/sendtransacsms
 */
class BrevoSmsService
{
    private bool $enabled;

    private ?string $apiKey;

    private string $sender;

    private string $defaultCountryCode;

    public function __construct()
    {
        $config = config('services.brevo');

        $this->enabled = (bool) ($config['enabled'] ?? false);
        $this->apiKey = $config['api_key'] ?? null;
        $this->sender = (string) ($config['sender'] ?? 'Barberia');
        $this->defaultCountryCode = (string) ($config['default_country_code'] ?? '52');
    }

    /**
     * ¿Está lista la integración para enviar SMS reales?
     * Si falta cualquier credencial o el flag está apagado, devolvemos false
     * para poder degradar con elegancia (registrar la notificación como
     * "fallido" en vez de reventar la petición del usuario).
     */
    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Envía un SMS de texto plano a un número de teléfono.
     *
     * @param  string  $rawPhone  Teléfono tal como está en la base de datos
     *                            (ej. "9511234567"); se normaliza a E.164.
     * @return bool  true si la API respondió correctamente.
     */
    public function sendText(string $rawPhone, string $message): bool
    {
        $to = $this->normalizePhone($rawPhone);

        if ($to === null) {
            Log::warning('Brevo SMS: teléfono inválido, no se envía mensaje.', [
                'raw_phone' => $rawPhone,
            ]);

            return false;
        }

        if (! $this->isConfigured()) {
            // Modo desarrollo / credenciales ausentes: dejamos rastro en el
            // log para poder verificar el contenido sin gastar SMS reales.
            Log::info('Brevo SMS (simulado, integración no configurada).', [
                'to' => $to,
                'message' => $message,
            ]);

            return false;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(8)
                ->post('https://api.brevo.com/v3/transactionalSMS/sms', [
                    'sender' => $this->sender,
                    'recipient' => $to,
                    'content' => $message,
                    'type' => 'transactional',
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Brevo SMS: la API respondió con error.', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Brevo SMS: excepción al enviar el mensaje.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normaliza un teléfono a formato E.164 con "+", que es lo que espera
     * la API de Brevo (ej. "+5219511234567").
     *
     * - Quita espacios, guiones, paréntesis y "+" previos.
     * - Si quedan 10 dígitos, se antepone el código de país por defecto.
     * - Si ya trae el código de país, se respeta.
     */
    private function normalizePhone(string $rawPhone): ?string
    {
        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = $this->defaultCountryCode.$digits;
        }

        // Un E.164 válido tiene entre 8 y 15 dígitos.
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return '+'.$digits;
    }
}
