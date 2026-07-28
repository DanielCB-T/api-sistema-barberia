<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * El carrito no es una tabla aparte: es la orden (Order) del usuario que
 * sigue en estado "carrito". Al hacer checkout (OrderController::checkout)
 * esa misma orden cambia de estado y deja de ser el carrito activo.
 */
class CartController extends Controller
{
    private const RELATIONS = ['items.product'];

    /**
     * GET /api/cart
     */
    public function show(Request $request)
    {
        $cart = $this->getOrCreateCart($request->user());

        return new OrderResource($cart->load(self::RELATIONS));
    }

    /**
     * POST /api/cart/items  { product_id, quantity }
     * Si el producto ya está en el carrito, suma la cantidad al item existente.
     */
    public function addItem(AddCartItemRequest $request)
    {
        $user = $request->user();
        $product = Product::findOrFail($request->product_id);
        $cart = $this->getOrCreateCart($user);

        $existing = $cart->items()->where('product_id', $product->id)->first();
        $requestedTotal = $request->integer('quantity') + ($existing?->quantity ?? 0);

        if ($requestedTotal > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Solo hay {$product->stock} unidades disponibles de \"{$product->name}\"."],
            ]);
        }

        if ($existing) {
            $existing->update(['quantity' => $requestedTotal]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $request->integer('quantity'),
                'unit_price' => $product->price,
            ]);
        }

        $this->recalculateTotal($cart);

        return (new OrderResource($cart->load(self::RELATIONS)))
            ->additional(['message' => 'Producto agregado al carrito.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/cart/items/{item}  { quantity }
     */
    public function updateItem(UpdateCartItemRequest $request, OrderItem $item)
    {
        $cart = $this->authorizeCartItem($request, $item);

        if ($request->integer('quantity') > $item->product->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Solo hay {$item->product->stock} unidades disponibles de \"{$item->product->name}\"."],
            ]);
        }

        $item->update(['quantity' => $request->integer('quantity')]);
        $this->recalculateTotal($cart);

        return new OrderResource($cart->load(self::RELATIONS));
    }

    /**
     * DELETE /api/cart/items/{item}
     */
    public function removeItem(Request $request, OrderItem $item)
    {
        $cart = $this->authorizeCartItem($request, $item);

        $item->delete();
        $this->recalculateTotal($cart);

        return new OrderResource($cart->load(self::RELATIONS));
    }

    private function getOrCreateCart($user): Order
    {
        return Order::query()->firstOrCreate(
            ['client_id' => $user->id, 'status' => 'carrito'],
            ['total' => 0]
        );
    }

    /**
     * El item debe pertenecer al carrito (no a una orden ya cerrada) del
     * usuario autenticado; el admin puede operar sobre cualquier carrito.
     */
    private function authorizeCartItem(Request $request, OrderItem $item): Order
    {
        $order = $item->order;

        if ($order->status !== 'carrito') {
            throw ValidationException::withMessages([
                'quantity' => ['Esta orden ya no es un carrito activo.'],
            ]);
        }

        $user = $request->user();
        if (! $user->isAdmin() && $order->client_id !== $user->id) {
            throw new AccessDeniedHttpException('No tienes permisos sobre este carrito.');
        }

        return $order;
    }

    private function recalculateTotal(Order $order): void
    {
        $total = $order->items()->get()->sum(fn ($item) => $item->quantity * (float) $item->unit_price);
        $order->update(['total' => $total]);
    }
}
