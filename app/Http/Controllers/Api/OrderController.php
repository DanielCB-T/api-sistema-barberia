<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrderController extends Controller
{
    private const RELATIONS = ['items.product', 'client'];

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * GET /api/orders
     *
     * - Cliente: solo ve SUS órdenes ya generadas (no el carrito en curso).
     * - Admin: ve todas, con filtro opcional ?status=.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 10);

        $query = Order::query()->with(self::RELATIONS)->where('status', '!=', 'carrito');

        if (! $user->isAdmin()) {
            $query->where('client_id', $user->id);
        }

        $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('created_at', 'desc');

        return OrderResource::collection($query->paginate($perPage));
    }

    /**
     * GET /api/orders/{order}
     */
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        if (! $user->isAdmin() && $order->client_id !== $user->id) {
            throw new AccessDeniedHttpException('No tienes permisos sobre esta orden.');
        }

        return new OrderResource($order->load(self::RELATIONS));
    }

    /**
     * POST /api/orders/checkout  { payment_method }
     *
     * Convierte el carrito activo del cliente en una orden generada: valida
     * existencias, descuenta stock y cambia el estado de "carrito" a "pagado".
     */
    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();

        $cart = Order::query()
            ->where('client_id', $user->id)
            ->where('status', 'carrito')
            ->with('items.product')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Tu carrito está vacío, agrega productos antes de generar la orden.'],
            ]);
        }

        foreach ($cart->items as $item) {
            if ($item->quantity > $item->product->stock) {
                throw ValidationException::withMessages([
                    'cart' => ["Ya no hay suficientes existencias de \"{$item->product->name}\"."],
                ]);
            }
        }

        DB::transaction(function () use ($cart, $request) {
            foreach ($cart->items as $item) {
                $item->product->decrement('stock', $item->quantity);
            }

            $cart->update([
                'status' => 'pagado',
                'payment_method' => $request->input('payment_method'),
            ]);
        });

        // Eventos: pedido realizado y pago aprobado (el checkout deja la
        // orden en estado "pagado"). Ambos generan notificaciones internas.
        $fresh = $cart->load(self::RELATIONS);
        $this->notifications->orderPlaced($fresh);
        $this->notifications->paymentApproved($fresh);

        return (new OrderResource($fresh))
            ->additional(['message' => 'Orden generada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }
}
