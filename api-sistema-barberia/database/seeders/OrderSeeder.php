<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', 'client')->get();
        $products = Product::all();

        for ($i = 0; $i < 12; $i++) {
            $client = $clients->random();
            $status = fake()->randomElement(['carrito', 'pagado', 'enviado', 'cancelado']);

            $order = Order::create([
                'client_id' => $client->id,
                'total' => 0,
                'status' => $status,
                'payment_method' => $status === 'pagado' || $status === 'enviado'
                    ? fake()->randomElement(['online', 'en_sucursal'])
                    : null,
            ]);

            $itemsCount = rand(1, 3);
            $total = 0;

            foreach ($products->random($itemsCount) as $product) {
                $quantity = rand(1, 3);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ]);

                $total += $quantity * $product->price;
            }

            $order->update(['total' => $total]);

            if ($order->payment_method === 'online') {
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $total,
                    'provider' => fake()->randomElement(['stripe', 'mercadopago']),
                    'transaction_id' => strtoupper(fake()->bothify('TXN-########')),
                    'status' => 'aprobado',
                ]);
            }
        }
    }
}
