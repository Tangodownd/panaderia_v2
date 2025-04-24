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
  public function store(Request $request)
  {
      \Log::info('OrderController@store - Request:', [
          'data' => $request->all(),
          'user_id' => auth()->id(),
          'cart_id_cookie' => $request->cookie('cart_id')
      ]);
      
      try {
          // Validar los datos de la orden
          $request->validate([
              'name' => 'required|string|max:255',
              'email' => 'required|email|max:255',
              'phone' => 'required|string|max:20',
              'shipping_address' => 'required|string',
              'payment_method' => 'required|in:cash,card,transfer',
          ]);

          // Iniciar transacción
          DB::beginTransaction();
          
          // Obtener el carrito actual usando el método existente
          $cart = $this->getCart($request);
          
          if (!$cart) {
              return response()->json([
                  'success' => false,
                  'message' => 'No se encontró un carrito válido'
              ], 400);
          }
          
          // Cargar los items del carrito
          $cartItems = CartItem::with('product')
              ->where('cart_id', $cart->id)
              ->get();
          
          if ($cartItems->isEmpty()) {
              return response()->json([
                  'success' => false,
                  'message' => 'El carrito está vacío'
              ], 400);
          }
          
          // Verificar stock disponible antes de procesar la orden
          $stockErrors = [];
          foreach ($cartItems as $item) {
              $product = Product::find($item->product_id);
              if (!$product) {
                  $stockErrors[] = "El producto ya no está disponible.";
                  continue;
              }
              
              if ($product->stock < $item->quantity) {
                  $stockErrors[] = "No hay suficiente stock para '{$product->name}'. Disponible: {$product->stock}, Solicitado: {$item->quantity}";
              }
          }
          
          if (!empty($stockErrors)) {
              DB::rollBack();
              return response()->json([
                  'success' => false,
                  'message' => 'Error de stock',
                  'errors' => $stockErrors
              ], 422);
          }
          
          // Calcular el total de forma explícita
          $total = 0;
          foreach ($cartItems as $item) {
              $total += $item->quantity * $item->price;
          }
          
          // Crear la orden
          $order = new Order();
          $order->user_id = auth()->id();
          $order->name = $request->name;
          $order->email = $request->email;
          $order->phone = $request->phone;
          $order->shipping_address = $request->shipping_address;
          $order->payment_method = $request->payment_method;
          $order->notes = $request->notes;
          $order->total = $total;
          $order->status = 'pending';
          $order->save();
          
          // Crear los items de la orden
          foreach ($cartItems as $item) {
              $orderItem = new OrderItem();
              $orderItem->order_id = $order->id;
              $orderItem->product_id = $item->product_id;
              $orderItem->quantity = $item->quantity;
              $orderItem->price = $item->price;
              $orderItem->save();

              // Actualizar el stock del producto
              $product = Product::find($item->product_id);
              if ($product) {
                  // Asegurarse de que el stock no sea negativo
                  $newStock = max(0, $product->stock - $item->quantity);
                  $product->stock = $newStock;
                  $product->save();
                  
                  \Log::info('Stock actualizado para producto', [
                      'product_id' => $product->id,
                      'previous_stock' => $product->stock + $item->quantity,
                      'new_stock' => $product->stock,
                      'quantity_ordered' => $item->quantity
                  ]);
              }
          }
          
          // Marcar el carrito como completado
          $cart->status = 'completed';
          $cart->save();
          
          // Crear un nuevo carrito activo
          $newCart = new Cart();
          if (auth()->check()) {
              $newCart->user_id = auth()->id();
          }
          $newCart->session_id = null;
          $newCart->total = 0;
          $newCart->status = 'active';
          $newCart->save();
          
          \Log::info('Nuevo carrito activo creado después de la orden', [
              'cart_id' => $newCart->id
          ]);
          
          // Confirmar transacción
          DB::commit();
          
          \Log::info('Orden creada correctamente:', ['order_id' => $order->id]);
          
          // Variable para indicar si se envió el mensaje de WhatsApp
          $whatsappSent = false;
          
          // Preparar y enviar mensaje de WhatsApp
          try {
              // Registrar el número de teléfono que se usará
              \Log::info('Preparando envío de WhatsApp', [
                  'telefono' => $request->phone,
                  'order_id' => $order->id
              ]);
              
              // Crear un mensaje personalizado para la orden
              $orderItems = '';
              foreach ($cartItems as $item) {
                  $orderItems .= "- " . $item->quantity . "x " . $item->product->name . "\n";
              }
              
              // Formatear el método de pago para el mensaje
              $paymentMethod = '';
              switch ($request->payment_method) {
                  case 'cash':
                      $paymentMethod = 'Efectivo';
                      break;
                  case 'card':
                      $paymentMethod = 'Tarjeta';
                      break;
                  case 'transfer':
                      $paymentMethod = 'Transferencia';
                      break;
                  default:
                      $paymentMethod = $request->payment_method;
              }
              
              // Crear el mensaje personalizado
              $whatsappMessage = "¡Gracias por tu compra en Panadería!\n\n" .
                               "*Orden #" . $order->id . "*\n" .
                               "Fecha: " . date('d/m/Y') . "\n" .
                               "Cliente: " . $request->name . "\n" .
                               "Total: $" . number_format($order->total, 2) . "\n" .
                               "Método de pago: " . $paymentMethod . "\n\n" .
                               "*Productos:*\n" . $orderItems . "\n" .
                               "*Dirección de entrega:*\n" . $request->shipping_address . "\n\n" .
                               "Tu pedido será procesado pronto. Para cualquier consulta, responde a este mensaje.";
              
              \Log::info('Mensaje de WhatsApp a enviar', [
                  'mensaje' => $whatsappMessage
              ]);
              
              // Intentar enviar primero con mensaje personalizado
              $messageSid = $this->sendWhatsAppMessage($request->phone, $whatsappMessage);
              
              // Si falla, intentar con la plantilla como respaldo
              if (!$messageSid) {
                  \Log::warning('No se pudo enviar mensaje personalizado, intentando con plantilla');
                  $messageSid = $this->sendWhatsAppTemplate($request->phone);
              }
              
              // Registrar si se envió el mensaje
              if ($messageSid) {
                  \Log::info('Mensaje de WhatsApp enviado para la orden', [
                      'order_id' => $order->id,
                      'message_sid' => $messageSid
                  ]);
                  $whatsappSent = true;
              } else {
                  \Log::warning('No se pudo enviar el mensaje de WhatsApp', [
                      'order_id' => $order->id
                  ]);
              }
          } catch (\Exception $e) {
              // Capturar cualquier error en el envío del mensaje, pero no afectar la creación de la orden
              \Log::error('Excepción al enviar mensaje de WhatsApp: ' . $e->getMessage(), [
                  'order_id' => $order->id,
                  'exception_class' => get_class($e),
                  'trace' => $e->getTraceAsString()
              ]);
          }
          
          // Establecer la cookie con el ID del nuevo carrito
          $response = response()->json([
              'success' => true,
              'message' => 'Orden creada correctamente',
              'order_id' => $order->id,
              'whatsapp_sent' => $whatsappSent
          ]);
          
          $response->cookie('cart_id', $newCart->id, 60 * 24 * 30);
          
          return $response;
      } catch (\Illuminate\Validation\ValidationException $e) {
          // Revertir transacción en caso de error de validación
          DB::rollBack();
          
          // Registrar el error
          \Log::error('Error de validación al crear orden: ' . json_encode($e->errors()));
          
          return response()->json([
              'success' => false,
              'message' => 'Error de validación',
              'errors' => $e->errors()
          ], 422);
      } catch (\Exception $e) {
          // Revertir transacción en caso de error
          DB::rollBack();
          
          // Registrar el error
          \Log::error('Error al crear orden: ' . $e->getMessage());
          \Log::error('Stack trace: ' . $e->getTraceAsString());
          
          return response()->json([
              'success' => false,
              'message' => 'Error al crear la orden: ' . $e->getMessage()
          ], 500);
      }
  }
}