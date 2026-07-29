<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integración con la API real de WhatsApp (Meta WhatsApp Cloud API).
 *
 * Responsabilidad única: enviar un mensaje de texto a un número de WhatsApp.
 * No sabe nada de citas ni de notificaciones internas; eso lo orquesta
 * NotificationService. Toda la configuración (token, phone number id, etc.)
 * vive en config/services.php -> 'whatsapp', que a su vez lee del .env.
 *
 * Documentación oficial:
 *   https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
 */
class WhatsAppService
{
    private bool $enabled;

    private ?string $token;

    private ?string $phoneNumberId;

    private string $apiVersion;

    private string $defaultCountryCode;

    public function __construct()
    {
        $config = config('services.whatsapp');

        $this->enabled = (bool) ($config['enabled'] ?? false);
        $this->token = $config['token'] ?? null;
        $this->phoneNumberId = $config['phone_number_id'] ?? null;
        $this->apiVersion = $config['api_version'] ?? 'v21.0';
        $this->defaultCountryCode = (string) ($config['default_country_code'] ?? '52');
    }

    /**
     * ¿Está lista la integración para enviar mensajes reales?
     * Si falta cualquier credencial o el flag está apagado, devolvemos false
     * para poder degradar con elegancia (registrar la notificación como
     * "fallido" en vez de reventar la petición del usuario).
     */
    public function isConfigured(): bool
    {
        return $this->enabled
            && ! empty($this->token)
            && ! empty($this->phoneNumberId);
    }

    /**
     * Envía un mensaje de texto plano a un número de teléfono.
     *
     * @param  string  $rawPhone  Teléfono tal como está en la base de datos
     *                            (ej. "9511234567"); se normaliza a E.164.
     * @return bool  true si la API respondió correctamente.
     */
    public function sendText(string $rawPhone, string $message): bool
    {
        $to = $this->normalizePhone($rawPhone);

        if ($to === null) {
            Log::warning('WhatsApp: teléfono inválido, no se envía mensaje.', [
                'raw_phone' => $rawPhone,
            ]);

            return false;
        }

        if (! $this->isConfigured()) {
            // Modo desarrollo / credenciales ausentes: dejamos rastro en el
            // log para poder verificar el contenido sin gastar mensajes reales.
            Log::info('WhatsApp (simulado, integración no configurada).', [
                'to' => $to,
                'message' => $message,
            ]);

            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(15)
                ->post(
                    "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages",
                    [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $to,
                        'type' => 'text',
                        'text' => [
                            'preview_url' => false,
                            'body' => $message,
                        ],
                    ]
                );

            if ($response->successful()) {
                return true;
            }

            Log::error('WhatsApp: la API respondió con error.', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp: excepción al enviar el mensaje.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normaliza un teléfono a formato E.164 sin el símbolo "+", que es lo
     * que espera la Cloud API (ej. "5219511234567").
     *
     * - Quita espacios, guiones, paréntesis y "+".
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

        return $digits;
    }
}
