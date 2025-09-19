<?php

// app/Http/Controllers/ChatCartController.php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatCartController extends Controller
{
  // POST /api/chat/cart/items
  // body: { items: [{product_id, quantity}, ...] }
  public function checkItems(Request $req) {
    $data = $req->validate([
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'required|integer|exists:products,id',
      'items.*.quantity' => 'required|integer|min:1',
    ]);

    // Trae productos y calcula availableStock() para cada uno
    $result = [];
    $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

    foreach ($data['items'] as $row) {
      $p = $products[$row['product_id']];
      $available = method_exists($p,'availableStock') ? $p->availableStock() : $p->stock; // fallback
      $ok = $row['quantity'] <= $available;
      $result[] = [
        'product_id' => $p->id,
        'requested'  => (int)$row['quantity'],
        'available'  => (int)$available,
        'ok'         => $ok,
        'message'    => $ok ? 'OK' : "Stock insuficiente. Disponible: {$available}"
      ];
    }
    return response()->json(['items'=>$result]);
  }

  // POST /api/chat/orders/confirm
  // body: { items:[{product_id,quantity}], customer:{name, phone, ...}, session_id?:string, ttl_minutes?:int }
  public function confirm(Request $req) {
    $payload = $req->validate([
      'items' => 'required|array|min:1',
      'items.*.product_id' => 'required|integer|exists:products,id',
      'items.*.quantity' => 'required|integer|min:1',
      'customer' => 'nullable|array',
      'session_id' => 'nullable|string',
      'ttl_minutes' => 'nullable|integer|min:5|max:120',
    ]);
    $ttl = $payload['ttl_minutes'] ?? 15;

    return DB::transaction(function () use ($payload, $ttl) {
      $ids = collect($payload['items'])->pluck('product_id')->all();

      // Lock FOR UPDATE para evitar carreras
      $products = Product::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

      // Revalidación final con reservas activas
      foreach ($payload['items'] as $row) {
        $p = $products[$row['product_id']];
        $available = method_exists($p,'availableStock') ? $p->availableStock() : $p->stock;

        if ($row['quantity'] > $available) {
          return response()->json([
            'success'=>false,
            'code'=>'INSUFFICIENT_STOCK',
            'product_id'=>$p->id,
            'available'=>$available,
            'requested'=>$row['quantity'],
            'message'=>"Stock insuficiente para {$p->name}. Disponible: {$available}",
          ], 422);
        }
      }

      // Crea la orden en estado pending
      $order = new Order();
      $order->status = 'pending';
      $order->customer_name = $payload['customer']['name'] ?? null;
      $order->customer_phone = $payload['customer']['phone'] ?? null;
      $order->total = 0;
      $order->save();

      $total = 0;
      foreach ($payload['items'] as $row) {
        $p = $products[$row['product_id']];

        // Reserva con TTL
        StockReservation::create([
          'product_id' => $p->id,
          'quantity'   => (int)$row['quantity'],
          'order_id'   => $order->id,
          'session_id' => $payload['session_id'] ?? null,
          'status'     => 'reserved',
          'expires_at' => now()->addMinutes($ttl),
        ]);

        // Crea OrderItem
        $item = new OrderItem([
          'product_id' => $p->id,
          'quantity'   => (int)$row['quantity'],
          'price'      => (float)$p->price,
        ]);
        $order->items()->save($item);

        $total += $item->quantity * $item->price;
      }

      $order->total = $total;
      $order->save();

      return response()->json([
        'success'=>true,
        'order_id'=>$order->id,
        'status'=>$order->status,
        'ttl_minutes'=>$ttl,
        'expires_at'=>now()->addMinutes($ttl)->toIso8601String(),
        'message'=>"Orden creada y stock reservado por {$ttl} minutos.",
      ], 201);
    });
  }
}
