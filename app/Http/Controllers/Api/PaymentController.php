<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreateCheckoutRequest;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PaymentController extends Controller
{
    /**
     * POST /api/payments/create  { appointment_id }
     *
     * Crea (o reutiliza) una Stripe Checkout Session en modo sandbox para
     * pagar una cita ya agendada con "pay_online" = true. El pago se marca
     * como confirmado únicamente cuando llega el webhook de Stripe
     * (checkout.session.completed), nunca por la simple redirección de
     * vuelta del cliente.
     */
    public function create(CreateCheckoutRequest $request)
    {
        $appointment = Appointment::with('service', 'payment')->findOrFail($request->appointment_id);

        $user = $request->user();
        if (! $user->isAdmin() && $appointment->client_id !== $user->id) {
            throw new AccessDeniedHttpException('No tienes permisos sobre esta cita.');
        }

        if ($appointment->payment && $appointment->payment->status === 'aprobado') {
            throw ValidationException::withMessages([
                'appointment_id' => ['Esta cita ya tiene un pago aprobado.'],
            ]);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'mxn',
                    'unit_amount' => (int) round($appointment->service->price * 100), // centavos
                    'product_data' => [
                        'name' => "Cita: {$appointment->service->name}",
                    ],
                ],
            ]],
            'metadata' => [
                'appointment_id' => (string) $appointment->id,
            ],
            'success_url' => config('app.frontend_url').'/dashboard/mis-citas?pago=exitoso',
            'cancel_url' => config('app.frontend_url').'/dashboard/mis-citas?pago=cancelado',
        ]);

        $payment = Payment::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'amount' => $appointment->service->price,
                'provider' => 'stripe',
                'transaction_id' => $session->id,
                'status' => 'pendiente',
            ]
        );

        return response()->json([
            'checkout_url' => $session->url,
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * POST /api/payments/webhook  (público, sin auth: lo llama Stripe)
     *
     * Verifica la firma con el secreto del webhook y actualiza el pago en
     * la base de datos según el evento recibido. Esta es la única fuente
     * de verdad de que un pago quedó confirmado.
     */
    public function webhook(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Webhook de Stripe rechazado: firma inválida.');

            return response()->json(['error' => 'Firma inválida.'], 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->markPayment($event->data->object, 'aprobado'),
            'checkout.session.expired', 'payment_intent.payment_failed' => $this->markPayment($event->data->object, 'rechazado'),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function markPayment(object $session, string $status): void
    {
        $payment = Payment::where('transaction_id', $session->id)->first();

        if (! $payment) {
            Log::warning("Webhook de Stripe: no se encontró un Payment para la sesión {$session->id}.");

            return;
        }

        $payment->update(['status' => $status]);
    }
}
