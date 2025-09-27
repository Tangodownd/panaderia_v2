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
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    /**
     * Obtener el carrito actual usando el ID de la cookie
     */
    private function getCart(Request $request)
    {
        $cartId = $request->cookie('cart_id');

        \Log::info('OrderController@getCart - Usando cookie', [
            'cookie_cart_id' => $cartId
        ]);

        if (!$cartId) {
            \Log::warning('No se encontró cart_id en la cookie');
            return null;
        }

        $cart = Cart::where('id', $cartId)
            ->where(function ($query) {
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
     * ====== HELPERS WHATSAPP (existentes) ======
     */
    private function sendWhatsAppMessage($to, $message)
    {
        try {
            $sid   = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');

            \Log::info('Intentando enviar mensaje de WhatsApp personalizado', [
                'numero_original' => $to,
                'mensaje' => $message
            ]);

            if (!$sid || !$token) {
                \Log::warning('Credenciales de Twilio no configuradas correctamente');
                return null;
            }

            $curlOptions = [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            ];

            $client = new Client($sid, $token, null, null, new \Twilio\Http\CurlClient($curlOptions));

            $formattedNumber = preg_replace('/[^0-9]/', '', $to);
            if (strlen($formattedNumber) > 10 && substr($formattedNumber, 0, 1) === '0') {
                $formattedNumber = substr($formattedNumber, 1);
            }
            $whatsappTo = 'whatsapp:+' . $formattedNumber;

            \Log::info('Enviando mensaje de WhatsApp personalizado', [
                'to' => $whatsappTo,
                'message' => $message
            ]);

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

    private function sendWhatsAppTemplate($to)
    {
        try {
            $sid   = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');

            \Log::info('Intentando enviar mensaje de WhatsApp con plantilla', [
                'numero_original' => $to
            ]);

            if (!$sid || !$token) {
                \Log::warning('Credenciales de Twilio no configuradas correctamente');
                return null;
            }

            $curlOptions = [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            ];

            $client = new Client($sid, $token, null, null, new \Twilio\Http\CurlClient($curlOptions));

            $formattedNumber = preg_replace('/[^0-9]/', '', $to);
            if (strlen($formattedNumber) > 10 && substr($formattedNumber, 0, 1) === '0') {
                $formattedNumber = substr($formattedNumber, 1);
            }
            $whatsappTo = 'whatsapp:+' . $formattedNumber;

            $date = date('d/m/Y');
            $time = date('h:i A');

            $contentVariables = json_encode([
                "1" => $date,
                "2" => $time
            ]);

            \Log::info('Enviando mensaje de WhatsApp con plantilla', [
                'to' => $whatsappTo,
                'contentSid' => 'HXb5b62575e6e4ff6129ad7c8efe1f983e',
                'contentVariables' => $contentVariables
            ]);

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
     * ====== NUEVOS HELPERS (no intrusivos) ======
     */
    private function buildOrderSummaryText(Order $order, $items): string
    {
        $lines = [];
        foreach ($items as $it) {
            $name  = $it->product->name ?? $it->name ?? ('Producto #' . $it->product_id);
            $qty   = (int) $it->quantity;

            // Fallback: unit_price -> price -> product->price
            $unit = (float) (($it->unit_price ?? 0) ?: ($it->price ?? 0) ?: (optional($it->product)->price ?? 0));

            $lines[] = "- {$name} x{$qty} ($" . number_format($unit, 2) . " c/u)";
        }
        $lista = implode("\n", $lines);
        $total = number_format((float) $order->total, 2);

        return "Pedido #{$order->id}\n"
             . "{$order->name}\n"
             . "Tel: {$order->phone}\n\n"
             . "Detalle:\n{$lista}\n\n"
             . "Total: \${$total}";
    }

    private function bankInstructionsFor(string $method): string
    {
        return match ($method) {
            'zelle' => "Zelle: correo@ejemplo.com\nNombre: Panadería Orquídea de Oro",
            'mobile' => "Pago móvil: 0412-1234567\nBanco: Bxxxx\nRIF: J-12345678-9",
            default => "Transferencia:\nBanco: Bxxxx\nCuenta: 0102-0000-00-0000000000\nTitular: Panadería Orquídea de Oro\nRIF: J-12345678-9",
        };
    }

    private function sendPaymentVerifiedUnified(Order $order, string $method, ?string $reference): void
    {
        $items   = OrderItem::where('order_id', $order->id)->with('product')->get();
        $summary = $this->buildOrderSummaryText($order, $items);
        $invoiceUrl = url("/api/orders/{$order->id}/invoice.pdf");
        $pretty = match ($method) {
            'mobile' => 'pago móvil',
            'zelle'  => 'Zelle',
            default  => 'transferencia',
        };
        $refTxt = $reference ? " (Ref: {$reference})" : '';

        $msg = "Hemos verificado tu {$pretty}{$refTxt}. ✅\n\n"
             . "{$summary}\n\n"
             . "¡Pedido #{$order->id} creado! Te contactamos al {$order->phone}. ¡Gracias!";

        $this->sendWhatsAppMessage($order->phone, $msg);
    }

    /**
     * Crear una nueva orden
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        $cart = $this->getCart($request);
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'No hay carrito activo'], 400);
        }

        $cartItems = CartItem::with('product')->where('cart_id', $cart->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío'], 400);
        }

        // Revalidar stock (solo para informar antes de crear)
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
            return response()->json(['success' => false, 'message' => 'Error de stock', 'errors' => $stockErrors], 422);
        }

        // Calcular total
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->quantity * $item->price;
        }

        // Crear orden
        $order = new Order();
        $order->user_id          = auth()->id();
        $order->name             = $request->name;
        $order->email            = $request->email;
        $order->phone            = $request->phone;
        $order->shipping_address = $request->shipping_address;
        $order->payment_method   = $request->payment_method; // cash|transfer|zelle|mobile
        $order->notes            = $request->notes;
        $order->total            = $total;

        // Guardar referencia si viene
        if ($request->filled('payment_reference')) {
            $order->payment_reference = $request->input('payment_reference');
        }

        // Estado inicial por método
        $order->status = $request->payment_method === 'cash' ? 'cash_on_delivery' : 'awaiting_payment';
        $order->save();

        // Crear items
        foreach ($cartItems as $ci) {
            $orderItem = new OrderItem([
                'order_id'   => $order->id,
                'product_id' => $ci->product_id,
                'quantity'   => $ci->quantity,
                'price'      => $ci->price,
            ]);
            $orderItem->save();
        }

        // Cerrar carrito y abrir otro
        $cart->status = 'completed';
        $cart->save();

        $newCart = new Cart();
        if (auth()->check()) {
            $newCart->user_id = auth()->id();
        }
        $newCart->session_id = null;
        $newCart->total      = 0;
        $newCart->status     = 'active';
        $newCart->save();

        DB::commit();

        // ===== Mensajería WhatsApp según método =====
        $items   = OrderItem::where('order_id', $order->id)->with('product')->get();
        $summary = $this->buildOrderSummaryText($order, $items);

        $method = $order->payment_method;

        if ($method === 'cash') {
            $msg = "¡Gracias por tu pedido!\n\n{$summary}\n\n"
                 . "Método: EFECTIVO.\n"
                 . "Un administrador coordinará la entrega.";
            $this->sendWhatsAppMessage($order->phone, $msg);

        } else {
            $reference = $order->payment_reference;

            if (!empty($reference)) {
                $this->confirmOrderInternally($order->id);
                $this->sendPaymentVerifiedUnified($order, $method, $reference);
            } else {
                $datos = $this->bankInstructionsFor($method);
                $msg = "¡Pedido recibido!\n\n{$summary}\n\n"
                     . "Método: {$method}.\n\n"
                     . "{$datos}\n\n"
                     . "Indica el número de referencia o los últimos 4 dígitos para confirmar tu pago.";
                $this->sendWhatsAppMessage($order->phone, $msg);
            }
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'status' => $order->status
        ], 201);
    }

    /**
     * Subida de comprobante + referencia ➜ Auto-confirmación (no efectivo)
     */
    public function uploadPaymentProof(Request $request, $id)
    {
        $request->validate([
            'payment_proof'     => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'payment_reference' => 'required|string|max:100',
        ], [
            'payment_reference.required' => 'Debes indicar el número de referencia del pago.',
        ]);

        $order = Order::findOrFail($id);

        if (!in_array($order->payment_method, ['transfer', 'zelle', 'mobile'])) {
            return response()->json(['success' => false, 'message' => 'Este flujo es solo para pagos electrónicos'], 422);
        }


        $path = $request->file('payment_proof')->store('payment_proofs', 'public');
        $order->payment_proof_path = $path;
        $order->payment_reference  = $request->input('payment_reference');
        $order->save();

        DB::transaction(function () use ($order) {
            $items = OrderItem::where('order_id', $order->id)->get();

            $productIds = $items->pluck('product_id')->unique();
            $products   = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $it) {
                $p = $products[$it->product_id] ?? null;
                if (!$p) {
                    abort(422, 'Producto de la orden no existe');
                }
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

        $this->sendPaymentVerifiedUnified($order, $order->payment_method, $order->payment_reference);

        return response()->json([
            'success'           => true,
            'message'           => 'Comprobante recibido y pago confirmado.',
            'payment_proof_url' => asset('storage/' . $path),
            'order_status'      => $order->status,
        ]);
    }

    /**
     * Confirmación manual (admin) – para EFECTIVO o contingencias
     */
    public function confirmPayment(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $order = Order::lockForUpdate()->findOrFail($id);
            if (!in_array($order->status, ['awaiting_payment', 'awaiting_review', 'cash_on_delivery'])) {
                abort(422, 'Estado de orden inválido para confirmar pago.');
            }

            $items = OrderItem::where('order_id', $order->id)->get();

            $productIds = $items->pluck('product_id')->unique();
            $products   = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

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

        $order = Order::findOrFail($id);
        $this->sendPaymentVerifiedUnified($order, $order->payment_method, $order->payment_reference);

        return response()->json(['success' => true, 'message' => 'Pago confirmado y stock descontado']);
    }

    /**
     * Confirmación interna (si la necesitas en otros flujos)
     */
    private function confirmOrderInternally(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            $items = OrderItem::where('order_id', $order->id)->get();
            $productIds = $items->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

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

    /**
     * Detalle de orden (para la factura del front)
     * 🔧 Cambio: `price` con fallback para evitar 0.00 cuando venga del chatbot.
     */
    public function getOrderDetails($id)
    {
        $order = Order::findOrFail($id);

        $items = OrderItem::where('order_id', $order->id)
            ->with('product')
            ->get();

        $payloadItems = $items->map(function ($it) {
            $productName  = $it->product->name ?? ($it->name ?? ('Producto #' . $it->product_id));
            $fallbackUnit = (float) (($it->unit_price ?? 0) ?: ($it->price ?? 0) ?: (optional($it->product)->price ?? 0));

            return [
                'id'          => $it->id,
                'product_id'  => $it->product_id,
                'name'        => $productName,
                'quantity'    => (int) $it->quantity,
                'price'       => $fallbackUnit,             // 👈 ahora nunca 0 si existe precio del producto
                'unit_price'  => (float) ($it->unit_price ?? 0),
                'product'     => [
                    'id'    => $it->product->id ?? null,
                    'name'  => $productName,
                    'price' => (float) (optional($it->product)->price ?? 0),
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

    // === NUEVO: Normalizador de estado para el admin ===
    private function formatStatusForAdmin(string $status): array
    {
        return match ($status) {
            'awaiting_payment'   => ['label' => 'Pendiente',   'badge' => 'warning'],
            'cash_on_delivery'   => ['label' => 'Pendiente',   'badge' => 'warning'],
            'paid'               => ['label' => 'Completado',  'badge' => 'success'],
            'awaiting_review'    => ['label' => 'En revisión', 'badge' => 'secondary'],
            default              => ['label' => 'Desconocido', 'badge' => 'secondary'],
        };
    }

    // === Feed recientes para admin ===
    public function getRecentOrders(Request $request)
    {
        $orders = Order::orderByDesc('id')->limit(20)->get();

        $payload = $orders->map(function (Order $o) {
            $statusInfo = $this->formatStatusForAdmin($o->status ?? '');
            return [
                'id'                  => $o->id,
                'code'                => sprintf('ORD-%04d', $o->id),
                'created_at'          => $o->created_at,
                'customer_name'       => $o->name,
                'status'              => $o->status,
                'status_label'        => $statusInfo['label'],
                'status_badge'        => $statusInfo['badge'],
                'total'               => (float) $o->total,
                'payment_method'      => $o->payment_method,
                'payment_reference'   => $o->payment_reference ?? null,
                'payment_verified_at' => $o->payment_verified_at,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $payload]);
    }

    // === Completar orden en efectivo ===
public function completeCashOrder(Request $request, int $id)
{
    DB::transaction(function () use ($id) {
        $order = Order::lockForUpdate()->findOrFail($id);

        if (!in_array($order->status, ['cash_on_delivery', 'pending'])) {
            abort(422, 'Solo puedes completar órdenes en efectivo o pendientes.');
        }

        $items = OrderItem::where('order_id', $order->id)->get();

        $productIds = $items->pluck('product_id')->unique();
        $products   = Product::whereIn('id', $productIds)
                        ->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $it) {
            $p = $products[$it->product_id] ?? null;
            if (!$p) abort(422, 'Producto de la orden no existe');
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

    return response()->json(['success' => true, 'message' => 'Orden marcada como completada.']);
}


    public function invoicePdf($id)
    {
        $order = \App\Models\Order::with(['items.product'])->findOrFail($id);

        // 🔧 Fallback robusto para cada ítem
        $items = $order->items->map(function($it){
            $name  = $it->product->name ?? $it->name ?? ('Producto #'.$it->product_id);
            $qty   = (int) $it->quantity;
            $unit  = (float) (($it->unit_price ?? 0) ?: ($it->price ?? 0) ?: (optional($it->product)->price ?? 0));
            $total = $qty * $unit;
            return [
                'name'  => $name,
                'qty'   => $qty,
                'price' => $unit,
                'total' => $total,
            ];
        });

        $subtotal = (float) $items->sum('total');
        // Si tu orden ya trae tax/total correctos, puedes usarlos. Aquí mantenemos cálculo simple.
        $taxRate  = $order->tax_rate ?? 0.16;
        $tax      = $order->tax ?? round($subtotal * $taxRate, 2);
        $total    = $order->total ?? ($subtotal + $tax);

        $data = [
            'order'      => $order,
            'items'      => $items,
            'subtotal'   => $subtotal,
            'tax'        => $tax,
            'total'      => $total,
            'created_at' => optional($order->created_at)->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('invoices.order', $data)->setPaper('A4');
        return $pdf->download(sprintf('Factura_Pedido_%s.pdf', $order->id));
    }
}
