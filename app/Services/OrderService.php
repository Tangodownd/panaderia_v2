<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
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
    
    public function calculateTotal($items)
    {
        $total = 0;
        
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $total += $product->price * $item['quantity'];
        }
        
        return $total;
    }
    
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
            
            // Crear la orden
            $order = Order::create([
                'user_id' => $orderData['user_id'],
                'status' => 'pending',
                'shipping_address' => $orderData['shipping_address'],
                'payment_method' => $orderData['payment_method'],
                'total' => $this->calculateTotal($orderData['items'])
            ]);
            
            // Crear los items de la orden y actualizar stock
            foreach ($orderData['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price
                ]);
                
                // Actualizar stock
                $product = Product::find($item['product_id']);
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
}