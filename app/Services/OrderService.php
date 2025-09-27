<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * ✅ Valida stock de un producto puntual.
     */
    public function validateStock($productId, $quantity)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return [
                'valid' => false,
                'message' => 'Producto no encontrado'
            ];
        }
        
        if ($product->stock < $quantity) {
            return [
                'valid' => false,
                'message' => "No hay suficiente stock para el producto. Stock disponible: {$product->stock}"
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * ✅ Calcula el total a partir de un arreglo de items [product_id, quantity]
     *    usando el precio actual del producto en BD.
     */
    public function calculateTotal($items)
    {
        $total = 0;
        
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $total += $product->price * $item['quantity'];
            }
        }
        
        return $total;
    }
    
    /**
     * ✅ Crea una orden a partir de un payload manual (flujo tradicional que ya tenías).
     *
     * $orderData esperado:
     *  - user_id
     *  - shipping_address
     *  - payment_method
     *  - items: [ [product_id, quantity], ... ]
     *  - (opcionales) name, email, phone, session_id, payment_reference, notes
     *
     * Nota: Aquí se mantiene el descuento de stock INMEDIATO (como ya lo tenías).
     * Úsalo cuando quieras que el pedido bloquee stock al momento (p. ej., flujo manual controlado).
     */
    public function processOrder($orderData)
    {
        // Validar stock para todos los productos
        foreach ($orderData['items'] as $item) {
            $stockValidation = $this->validateStock($item['product_id'], $item['quantity']);
            
            if (!$stockValidation['valid']) {
                return [
                    'success' => false,
                    'message' => $stockValidation['message']
                ];
            }
        }
        
        try {
            DB::beginTransaction();
            
            // Estado por método de pago (si existe)
            $status = 'pending';
            if (!empty($orderData['payment_method']) && in_array($orderData['payment_method'], ['transfer','card'])) {
                $status = 'awaiting_payment';
            }

            /** @var Order $order */
            $order = Order::create([
                'user_id'           => $orderData['user_id'] ?? null,
                'session_id'        => $orderData['session_id'] ?? (session()->has('_token') ? session()->getId() : null),
                'status'            => $status,
                'shipping_address'  => $orderData['shipping_address'] ?? 'PENDIENTE',
                'payment_method'    => $orderData['payment_method'] ?? 'PENDIENTE',
                'payment_reference' => $orderData['payment_reference'] ?? null,
                'name'              => $orderData['name'] ?? (auth()->check() ? auth()->user()->name : 'Cliente web'),
                'email'             => $orderData['email'] ?? (auth()->check() ? auth()->user()->email : null),
                'phone'             => $orderData['phone'] ?? null,
                'notes'             => $orderData['notes'] ?? null,
                'total'             => $this->calculateTotal($orderData['items']),
            ]);
            
            // Crear los items de la orden y actualizar stock (INMEDIATO)
            foreach ($orderData['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \RuntimeException('Producto no encontrado al crear la orden.');
                }
                
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'total'      => $product->price * $item['quantity'],
                ]);
                
                // Descuento inmediato (manteniendo tu lógica original en este método)
                $product->decreaseStock($item['quantity']);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'order' => $order
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => 'Error al procesar la orden: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🆕 Crear una orden desde Carrito (para chatbot u otros flujos).
     *
     * Cambios CLAVE:
     * - Por defecto NO descuenta stock al crear si el método es electrónico (awaiting_payment).
     * - Puedes forzar descuento inmediato con $payload['deduct_now'] = true (ej. efectivo).
     *
     * @param Cart  $cart
     * @param array $payload (name, phone, shipping_address, payment_method, payment_reference, notes, user_id, email, status, deduct_now=false)
     * @return Order
     */
    public function createOrderFromCart(Cart $cart, array $payload = []): Order
    {
        if ($cart->status !== 'open') {
            throw new \RuntimeException('El carrito no está abierto.');
        }

        $items = CartItem::with('product')->where('cart_id', $cart->id)->get();
        if ($items->isEmpty()) {
            throw new \RuntimeException('El carrito está vacío.');
        }

        return DB::transaction(function () use ($cart, $items, $payload) {

            // Total robusto: usa el total del carrito si viene calculado, si no, suma items.
            $total = $cart->total ?? $items->reduce(
                fn($a, $i) => $a + ((float)($i->price ?? ($i->product->price ?? 0)) * (int)$i->quantity),
                0.0
            );

            // Estado según método de pago
            $status = $payload['status'] ?? 'pending';
            $pm     = $payload['payment_method'] ?? 'PENDIENTE';

            if (!empty($pm) && in_array($pm, ['transfer','card'])) {
                $status = 'awaiting_payment';
            }

            $deductNow = (bool)($payload['deduct_now'] ?? false);

            /** @var Order $order */
            $order = Order::create([
                'user_id'           => $payload['user_id'] ?? auth()->id(),
                'session_id'        => $cart->session_id ?? session()->getId(),
                'total'             => $total,
                'status'            => $status,
                'shipping_address'  => $payload['shipping_address'] ?? 'PENDIENTE',
                'payment_method'    => $pm,
                'payment_reference' => $payload['payment_reference'] ?? null,
                'name'              => $payload['name'] ?? (auth()->check() ? auth()->user()->name : 'Cliente web'),
                'email'             => $payload['email'] ?? (auth()->check() ? auth()->user()->email : null),
                'phone'             => $payload['phone'] ?? null,
                'notes'             => $payload['notes'] ?? 'Creado desde chatbot',
            ]);

            // Insertar items
            foreach ($items as $i) {
                $product = $i->product;
                if (!$product) {
                    throw new \RuntimeException('Producto no encontrado en un item del carrito.');
                }

                $unitPrice = (float)($i->price ?? $product->price);
                $qty       = (int)$i->quantity;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $qty,
                    'price'      => $unitPrice,
                    'total'      => $unitPrice * $qty,
                ]);

                // 🔁 Descuento de stock:
                // - Si deseas descontar YA (efectivo u otro caso), pasa deduct_now=true en $payload.
                // - Para electrónicos (awaiting_payment) NO descontamos aquí; se hará en confirmAndDiscountStock().
                if ($deductNow) {
                    $product->decreaseStock($qty);
                }
            }

            // Cerrar carrito
            $cart->status = 'closed';
            $cart->save();

            return $order;
        });
    }

    /**
     * 🆕 Confirma una orden (descuenta stock y marca como pagada).
     *    Úsalo cuando se verifique el pago (referencia/comprobante o confirmación admin).
     *
     * @param int $orderId
     * @return Order  (orden ya actualizada)
     */
    public function confirmAndDiscountStock(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            /** @var Order $order */
            $order = Order::lockForUpdate()->findOrFail($orderId);

            // Si ya está pagada, no hacemos nada (idempotencia básica)
            if ($order->status === 'paid') {
                return $order;
            }

            // Traer items
            $items = OrderItem::where('order_id', $order->id)->get();

            // Bloquear y validar stock de todos los productos
            $productIds = $items->pluck('product_id')->unique();
            $products   = Product::whereIn('id', $productIds)
                            ->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $it) {
                $p = $products[$it->product_id] ?? null;
                if (!$p) {
                    throw new \RuntimeException('Producto de la orden no existe');
                }
                if ($p->stock < $it->quantity) {
                    throw new \RuntimeException("Stock insuficiente para {$p->name}. Disponible: {$p->stock}");
                }
            }

            // Descontar
            foreach ($items as $it) {
                $p = $products[$it->product_id];
                $p->stock = max(0, $p->stock - $it->quantity);
                $p->save();
            }

            // Marcar como pagada
            $order->status = 'paid';
            $order->payment_verified_at = now();
            $order->save();

            return $order;
        });
    }
}
