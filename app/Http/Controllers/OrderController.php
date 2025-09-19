<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;

class OrderController extends Controller
{
  /**
   * Obtener el carrito actual usando el ID de la cookie
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \App\Models\Cart|null
   */
  private function getCart(Request $request)
  {
      // Obtener el cart_id de la cookie
      $cartId = $request->cookie('cart_id');
      
      \Log::info('OrderController@getCart - Usando cookie', [
          'cookie_cart_id' => $cartId
      ]);
      
      if (!$cartId) {
          \Log::warning('No se encontró cart_id en la cookie');
          return null;
      }
      
      // Buscar el carrito por su ID
      $cart = Cart::where('id', $cartId)
                ->where(function($query) {
                    $query->where('status', 'active')
                          ->orWhereNull('status');
                })
                ->first();
      
      if ($cart) {
          \Log::info('Carrito encontrado por cookie', ['cart_id' => $cart->id]);
      } else {
          \Log::warning('Carrito no encontrado con el ID de la cookie', ['cookie_cart_id' => $cartId]);
      }
      
      return $cart;
  }

  /**
   * Enviar mensaje de WhatsApp personalizado con Twilio
   *
   * @param  string  $to
   * @param  string  $message
   * @return string|null
   */
  private function sendWhatsAppMessage($to, $message)
  {
      try {
          // Obtener credenciales de Twilio desde variables de entorno
          $sid = env('TWILIO_SID');
          $token = env('TWILIO_AUTH_TOKEN');
          
          \Log::info('Intentando enviar mensaje de WhatsApp personalizado', [
              'numero_original' => $to,
              'mensaje' => $message
          ]);
          
          // Verificar que tenemos las credenciales
          if (!$sid || !$token) {
              \Log::warning('Credenciales de Twilio no configuradas correctamente');
              return null;
          }
          
          // Configurar opciones de cURL para deshabilitar la verificación SSL
          $curlOptions = [
              CURLOPT_SSL_VERIFYHOST => 0,
              CURLOPT_SSL_VERIFYPEER => 0
          ];
          
          // Inicializar cliente de Twilio con las opciones personalizadas
          $client = new Client($sid, $token, null, null, new \Twilio\Http\CurlClient($curlOptions));
          
          // Formatear el número de teléfono para WhatsApp
          $formattedNumber = preg_replace('/[^0-9]/', '', $to);
          
          // Si el número comienza con 0, eliminarlo
          if (strlen($formattedNumber) > 10 && substr($formattedNumber, 0, 1) === '0') {
              $formattedNumber = substr($formattedNumber, 1);
          }
          
          // Formatear el número para WhatsApp
          $whatsappTo = 'whatsapp:+' . $formattedNumber;
          
          \Log::info('Enviando mensaje de WhatsApp personalizado', [
              'to' => $whatsappTo,
              'message' => $message
          ]);
          
          // Enviar el mensaje con texto personalizado
          $messageResponse = $client->messages->create(
              $whatsappTo,
              [
                  'from' => 'whatsapp:+14155238886',
                  'body' => $message
              ]
          );
          
          \Log::info('Mensaje de WhatsApp enviado exitosamente', [
              'to' => $whatsappTo,
              'message_sid' => $messageResponse->sid,
              'status' => $messageResponse->status
          ]);
          
          return $messageResponse->sid;
      } catch (\Exception $e) {
          \Log::error('Error al enviar mensaje de WhatsApp: ' . $e->getMessage(), [
              'error_code' => $e->getCode(),
              'error_line' => $e->getLine(),
              'error_file' => $e->getFile()
          ]);
          return null;
      }
  }

  /**
   * Enviar mensaje de WhatsApp con Twilio usando plantilla
   *
   * @param  string  $to
   * @return string|null
   */
  private function sendWhatsAppTemplate($to)
  {
      try {
          // Obtener credenciales de Twilio desde variables de entorno
          $sid = env('TWILIO_SID');
          $token = env('TWILIO_AUTH_TOKEN');
          
          \Log::info('Intentando enviar mensaje de WhatsApp con plantilla', [
              'numero_original' => $to
          ]);
          
          // Verificar que tenemos las credenciales
          if (!$sid || !$token) {
              \Log::warning('Credenciales de Twilio no configuradas correctamente');
              return null;
          }
          
          // Configurar opciones de cURL para deshabilitar la verificación SSL
          $curlOptions = [
              CURLOPT_SSL_VERIFYHOST => 0,
              CURLOPT_SSL_VERIFYPEER => 0
          ];
          
          // Inicializar cliente de Twilio con las opciones personalizadas
          $client = new Client($sid, $token, null, null, new \Twilio\Http\CurlClient($curlOptions));
          
          // Formatear el número de teléfono para WhatsApp
          $formattedNumber = preg_replace('/[^0-9]/', '', $to);
          
          // Si el número comienza con 0, eliminarlo
          if (strlen($formattedNumber) > 10 && substr($formattedNumber, 0, 1) === '0') {
              $formattedNumber = substr($formattedNumber, 1);
          }
          
          // Formatear el número para WhatsApp
          $whatsappTo = 'whatsapp:+' . $formattedNumber;
          
          // Fecha y hora para la plantilla
          $date = date('d/m/Y');
          $time = date('h:i A');
          
          // Crear el JSON de variables de contenido
          $contentVariables = json_encode([
              "1" => $date,
              "2" => $time
          ]);
          
          \Log::info('Enviando mensaje de WhatsApp con plantilla', [
              'to' => $whatsappTo,
              'contentSid' => 'HXb5b62575e6e4ff6129ad7c8efe1f983e',
              'contentVariables' => $contentVariables
          ]);
          
          // Enviar el mensaje con la plantilla
          $messageResponse = $client->messages->create(
              $whatsappTo,
              [
                  'from' => 'whatsapp:+14155238886',
                  'contentSid' => 'HXb5b62575e6e4ff6129ad7c8efe1f983e',
                  'contentVariables' => $contentVariables
              ]
          );
          
          \Log::info('Mensaje de WhatsApp enviado exitosamente', [
              'to' => $whatsappTo,
              'message_sid' => $messageResponse->sid,
              'status' => $messageResponse->status
          ]);
          
          return $messageResponse->sid;
      } catch (\Exception $e) {
          \Log::error('Error al enviar mensaje de WhatsApp: ' . $e->getMessage(), [
              'error_code' => $e->getCode(),
              'error_line' => $e->getLine(),
              'error_file' => $e->getFile()
          ]);
          return null;
      }
  }

  /**
   * Crear una nueva orden
   */

  // OrderController.php (store)
public function store(Request $request)
{
    DB::beginTransaction();

    // 1) Traer carrito + items
    $cart = $this->getCart($request);
    $cartItems = CartItem::with('product')->where('cart_id', $cart->id)->get();
    if ($cartItems->isEmpty()) {
        return response()->json(['success'=>false,'message'=>'El carrito está vacío'], 400);
    }

    // 2) Revalidar stock (actualmente ya lo haces así) :contentReference[oaicite:6]{index=6}
    $stockErrors = [];
    foreach ($cartItems as $item) {
        $product = Product::find($item->product_id);
        if (!$product) { $stockErrors[] = "El producto ya no está disponible."; continue; }
        if ($product->stock < $item->quantity) {
            $stockErrors[] = "No hay suficiente stock para '{$product->name}'. Disponible: {$product->stock}, Solicitado: {$item->quantity}";
        }
    }
    if (!empty($stockErrors)) {
        DB::rollBack();
        return response()->json(['success'=>false,'message'=>'Error de stock','errors'=>$stockErrors], 422);
    }

    // 3) Calcular total (igual a como lo haces) :contentReference[oaicite:7]{index=7}
    $total = 0;
    foreach ($cartItems as $item) { $total += $item->quantity * $item->price; }

    // 4) Crear orden en awaiting_payment (ANTES la creabas 'pending' y descontabas stock) :contentReference[oaicite:8]{index=8}
    $order = new Order();
    $order->user_id = auth()->id();
    $order->name = $request->name;
    $order->email = $request->email;
    $order->phone = $request->phone;
    $order->shipping_address = $request->shipping_address;
    $order->payment_method = $request->payment_method; // cash|transfer|zelle|mobile
    $order->notes = $request->notes;
    $order->total = $total;
    $order->status = $request->payment_method === 'cash' ? 'cash_on_delivery' : 'awaiting_payment';
    $order->save();

    // 5) Crear order_items (SIN tocar stock por ahora) :contentReference[oaicite:9]{index=9}
    foreach ($cartItems as $ci) {
        $orderItem = new OrderItem([
          'order_id' => $order->id,
          'product_id' => $ci->product_id,
          'quantity' => $ci->quantity,
          'price' => $ci->price,
        ]);
        $orderItem->save();
    }

    // 6) Marcar carrito como completed y crear otro (igual que hoy) :contentReference[oaicite:10]{index=10}
    $cart->status = 'completed'; $cart->save();
    $newCart = new Cart(); if (auth()->check()) { $newCart->user_id = auth()->id(); }
    $newCart->session_id = null; $newCart->total = 0; $newCart->status = 'active'; $newCart->save();

    DB::commit();

    // 7) WhatsApp opcional (ya tienes el envío) — lo puedes conservar ajustando el texto según método. :contentReference[oaicite:11]{index=11}

    return response()->json(['success'=>true,'order_id'=>$order->id,'status'=>$order->status], 201);
}
public function uploadPaymentProof(Request $request, $id)
{
    $request->validate([
        'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
    ]);

    $order = Order::findOrFail($id);
    if (!in_array($order->status, ['awaiting_payment','cash_on_delivery'])) {
        return response()->json(['success'=>false,'message'=>'La orden no admite comprobante en este estado'], 422);
    }

    $path = $request->file('payment_proof')->store('payment_proofs', 'public');
    $order->payment_proof_path = $path;
    // Si quieres: $order->status = 'awaiting_review';
    $order->save();

    return response()->json([
        'success'=>true,
        'message'=>'Comprobante recibido',
        'payment_proof_url'=> asset('storage/'.$path),
        'order_status'=>$order->status,
    ]);
}
public function confirmPayment(Request $request, $id)
{
    // opcional: validar rol admin aquí si no usas middleware
    DB::transaction(function () use ($id) {
        $order = Order::lockForUpdate()->findOrFail($id);
        if (!in_array($order->status, ['awaiting_payment','awaiting_review','cash_on_delivery'])) {
            abort(422, 'Estado de orden inválido para confirmar pago.');
        }

        $items = OrderItem::where('order_id',$order->id)->get();

        // Bloquea productos y descuenta stock aquí (esto estaba en store()) :contentReference[oaicite:13]{index=13}
        $productIds = $items->pluck('product_id')->unique();
        $products = Product::whereIn('id',$productIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $it) {
            $p = $products[$it->product_id] ?? null;
            if (!$p) { abort(422, 'Producto de la orden no existe'); }
            if ($p->stock < $it->quantity) {
                abort(422, "Stock insuficiente para {$p->name}. Disponible: {$p->stock}");
            }
            $p->stock = max(0, $p->stock - $it->quantity);
            $p->save();
        }

        $order->status = 'paid';
        $order->payment_verified_at = now();
        $order->save();
    });

    return response()->json(['success'=>true,'message'=>'Pago confirmado y stock descontado']);
}
private function confirmOrderInternally(int $orderId): void
{
    DB::transaction(function () use ($orderId) {
        $order = Order::lockForUpdate()->findOrFail($orderId);
        if (!in_array($order->status, ['awaiting_payment','awaiting_review'])) {
            abort(422, 'Estado de orden inválido para confirmar pago.');
        }

        $items = OrderItem::where('order_id', $order->id)->get();
        $productIds = $items->pluck('product_id')->unique();
        $products = Product::whereIn('id',$productIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $it) {
            $p = $products[$it->product_id] ?? null;
            if (!$p) abort(422, 'Producto no existe');
            if ($p->stock < $it->quantity) {
                abort(422, "Stock insuficiente para {$p->name}. Disponible: {$p->stock}");
            }
            $p->stock -= $it->quantity;
            $p->save();
        }

        $order->status = 'paid';
        $order->payment_verified_at = now();
        $order->save();
    });
}
public function getOrderDetails($id)
    {
        // Trae la orden y sus ítems con el producto (sin depender de relaciones en el modelo)
        $order = Order::findOrFail($id);

        $items = OrderItem::where('order_id', $order->id)
            ->with('product') // si tu OrderItem tiene relation product()
            ->get();

        // Mapea a una estructura que el front espera para la factura
        $payloadItems = $items->map(function ($it) {
            $productName  = $it->product->name ?? ($it->name ?? ('Producto #'.$it->product_id));
            $productPrice = $it->product->price ?? $it->price;

            return [
                'id'         => $it->id,
                'product_id' => $it->product_id,
                'name'       => $productName,
                'quantity'   => (int) $it->quantity,
                'price'      => (float) $it->price,       // precio unit. facturado
                'product'    => [
                    'id'    => $it->product->id ?? null,
                    'name'  => $productName,
                    'price' => (float) $productPrice,
                ],
            ];
        })->values();

        return response()->json([
            'id'               => $order->id,
            'created_at'       => $order->created_at,
            'status'           => $order->status,
            'name'             => $order->name,
            'email'            => $order->email,
            'phone'            => $order->phone,
            'shipping_address' => $order->shipping_address,
            'total'            => (float) $order->total,
            'items'            => $payloadItems,
        ]);
    }

}