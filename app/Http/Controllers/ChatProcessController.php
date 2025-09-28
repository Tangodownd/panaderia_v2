<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AiClient;
use Illuminate\Support\Str;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ChatSession;
use App\Services\OrderService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use App\Models\OrderItem;
use App\Models\Order;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\AnalyticsService;
use App\Support\DateRanges;


class ChatProcessController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private WhatsAppService $whatsapp,
        private AnalyticsService $analytics
    ) {}

    public function process(Request $req, AiClient $ai)
    {   
        $customerId = auth()->id();
        if (!$customerId && $req->bearerToken()) {
            $pat =PersonalAccessToken::findToken($req->bearerToken());
            if ($pat) $customerId = (int) $pat->tokenable_id;
        }

        // 🚫 Si no hay cliente logeado, rechazamos
        if (!$customerId) {
            return response()->json([
                'error' => 'Debes iniciar sesión como cliente para usar el asistente.'
            ], 401);
        }

        $sessionId = session()->getId();
        $text = trim((string) $req->input('text', ''));

        $conversationId = $this->getOrCreateConversationId($customerId);


        // 2) IA (opcional)
        $context = [
            'session_id'  => $sessionId,
            'cart_cookie' => $req->cookie('cart_id'),
            'user_id'     => auth()->id(),
        ];
        $nlu = $ai->interpret($text, $context);

        $intent   = $nlu['intent']   ?? 'SMALL_TALK';
        $entities = $nlu['entities'] ?? [];

        // Si estaba pidiendo un ID y cambió de intención, reseteamos el flow
        $st = $this->getChatState();
        if (($st->step ?? null) === 'ask_order_id' && $intent !== 'PROVIDE_ORDER_ID') {
            $this->clearFlow();
        }

        // 1) Wizard activo (AHORA sí, ya con intent evaluado y posible reset)
        $state = $this->getChatState();
        if ($state->step) {
            // Si seguimos en ask_order_id y el intent SÍ es PROVIDE_ORDER_ID, dejamos que el wizard maneje
            if ($state->step === 'ask_order_id' && $intent !== 'PROVIDE_ORDER_ID') {
                // por seguridad, limpia si alguien mete ruido aquí
                $this->clearFlow();
            } else {
                $reply = $this->handleWizardStep($state->step, $text, $conversationId);
                return response()->json([
                    'reply' => $reply,
                    'conversation_id' => $conversationId,
                ]);
            }
        }

        $reply = $this->routeIntent($intent, $entities, $nlu);

        return response()->json([
            'reply' => $reply,
            'conversation_id' => $conversationId,
        ]);
    }

private function routeIntent(string $intent, array $entities, array $nlu): string
{
    return match ($intent) {
        'ORDER_HISTORY'                => $this->handleOrderHistory($entities),
        'ORDER_DETAILS'                => $this->handleOrderHistory($entities), // usa order_id si viene
        'PROVIDE_ORDER_ID'             => $this->handleOrderIdReply($nlu['normalized_text'] ?? ($nlu['text'] ?? '')),

        'CREATE_ORDER', 'ADD_TO_CART'  => $this->handleAddToCart($entities),
        'CHECK_STOCK'                  => $this->handleCheckStock($entities),
        'ASK_PRICE'                    => $this->handleAskPrice($entities),
        'LIST_PRODUCTS'                => $this->handleListAvailableProducts($entities, $nlu),
        'RECOMMEND'                    => $this->handleRecommend(),
        'CONFIRM_ORDER'                => $this->handleConfirmOrder(),
        'ANALYTICS_REPORT'             => $this->handleAnalyticsReport($nlu),
        default                        => $nlu['reply'] ?? 'Puedo ayudarte a hacer tu pedido. ¿Qué deseas hoy?',
    };
}


    /* =========================
       🔹 Wizard Conversacional
       ========================= */
    private function getChatState(): ChatSession
    {
        $sid = session()->getId();
        return ChatSession::firstOrCreate(['session_id' => $sid], [
            'step' => null,
            'data' => [],
        ]);
    }

    private function setStep(?string $step, array $patch = []): void
    {
        $st = $this->getChatState();
        $data = array_merge($st->data ?? [], $patch);
        $st->update(['step' => $step, 'data' => $data]);
    }

    private function clearFlow(): void
    {
        $st = $this->getChatState();
        $st->update(['step' => null, 'data' => []]);
    }

    private function needFieldsForOrder(): array
    {
        $cart = $this->getActiveCart(request());
        $need = [];
        if (!$cart) return ['cart'];

        $st = $this->getChatState()->data ?? [];

        if (empty($st['phone']))   $need[] = 'phone';
        if (empty($st['name']))    $need[] = 'name';
        if (empty($st['address'])) $need[] = 'address';
        if (empty($st['payment_method'])) $need[] = 'payment_method';

        if (!empty($st['payment_method']) && in_array($st['payment_method'], ['transfer','mobile','zelle'])) {

            if (empty($st['payment_reference'])) $need[] = 'payment_reference';
        }
        return $need;
    }

    private function normalizePaymentMethod(string $text): ?string
    {
        $t = Str::lower($text);
        $t = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $t);

        if (Str::contains($t, ['efectivo','cash'])) return 'cash';
        if (Str::contains($t, ['zelle'])) return 'zelle';
        if (Str::contains($t, ['pago movil','pagomovil','movil','mobile'])) return 'mobile';
        if (Str::contains($t, ['transfer','transferencia'])) return 'transfer';

        return null;
    }


    private function isValidPhone(string $text): bool
    {
        return (bool) preg_match('/^(\+?\d[\d\s-]{9,16}\d)$/', trim($text));
    }

    private function handleWizardStep(string $step, string $text, int $conversationId): string
    {
        $st = $this->getChatState();

        switch ($step) {
            case 'ask_order_id':
                    return $this->handleOrderIdReply($text);

            case 'ask_phone':
                if (!$this->isValidPhone($text)) {
                    return '¿Me compartes un teléfono válido? (Ej: +58 424 1234567)';
                }
                $this->setStep('ask_name', ['phone' => trim($text)]);
                return 'Perfecto. ¿A nombre de quién registramos el pedido?';

            case 'ask_name':
                $name = Str::limit(trim($text), 80, '');
                if (mb_strlen($name) < 2) return '¿Podrías indicarme tu nombre y apellido?';
                $this->setStep('ask_address', ['name' => $name]);
                return 'Gracias. ¿Cuál es la dirección de entrega (o indica *Retiro en tienda*)?';

            case 'ask_address':
                $addr = Str::limit(trim($text), 180, '');
                if (mb_strlen($addr) < 3) return 'Necesito una dirección válida (o escribe *Retiro en tienda*).';
                $this->setStep('ask_payment_method', ['address' => $addr]);
                return '¿Cómo deseas pagar? (efectivo / transferencia / pago móvil / zelle)';

            case 'ask_payment_method':
                $pm = $this->normalizePaymentMethod($text);
                if (!$pm) return 'Método no reconocido. Opciones: efectivo, transferencia, pago móvil o zelle.';

                if (in_array($pm, ['transfer','mobile','zelle'])) {
                    $this->setStep('ask_payment_reference', ['payment_method' => $pm]);
                    $datos = $this->bankInstructionsFor($pm);
                    return $datos . "\n\nIndica el número de referencia o últimos 4 dígitos.\n(Al enviarla, cerraremos tu pedido automáticamente)";
                }

                $this->setStep('confirm_summary', ['payment_method' => $pm]);
                return $this->summaryMessage()." ¿Confirmas tu pedido? (sí/no)";

            case 'ask_payment_reference':
                $ref = Str::upper(Str::replace(' ', '', trim($text)));
                if (mb_strlen($ref) < 3) return 'Referencia muy corta. ¿Puedes ingresar el número nuevamente?';

                $this->setStep(null, ['payment_reference' => $ref]);

                try {
                    $order = $this->ensureOrderFromChat();
                    $order = $this->markOrderPaidNow($order, $ref);

                    $verifiedMsg = 'Hemos verificado tu pago. ✅';
                    $invoiceUrl  = route('orders.invoice.pdf', ['id' => $order->id]);
                    $finalMsg    = "¡Pedido #{$order->id} creado! Te contactaremos al {$order->phone}. ¡Gracias!\n"
                                 . "Descarga tu factura en PDF: {$invoiceUrl}";

                    $this->saveBotMessage($conversationId, $verifiedMsg, ['order_id' => $order->id, 'step' => 'verified']);
                    $this->saveBotMessage($conversationId, $finalMsg,   ['order_id' => $order->id, 'step' => 'final']);

                    try {
                        if (method_exists($this->whatsapp, 'sendPaymentVerifiedUnified')) {
                            $this->whatsapp->sendPaymentVerifiedUnified(
                                $order->phone,
                                $order,
                                $order->payment_method,
                                $order->payment_reference,
                                $invoiceUrl
                            );
                        } else {
                            $this->whatsapp->sendCustomMessage(
                                $order->phone,
                                "Pago verificado ✅ (Ref: {$order->payment_reference}). Pedido #{$order->id}. Factura: {$invoiceUrl}"
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning('WhatsApp unificado falló: '.$e->getMessage(), ['order_id' => $order->id]);
                    }

                    $this->clearFlow();

                    return $verifiedMsg . "\n\n" . $finalMsg;

                } catch (\Throwable $e) {
                    Log::error('Error al cerrar orden inmediata desde chat: '.$e->getMessage());
                    return 'Tuvimos un inconveniente al confirmar tu pago. Intenta de nuevo o escribe "ayuda".';
                }

            case 'confirm_summary':
                $t = Str::lower($text);
                if (Str::contains($t, ['si','sí','confirmo','confirmar','ok'])) {
                    $msg = $this->createOrderFromChatCash();
                    $this->clearFlow();
                    return $msg;
                }
                if (Str::contains($t, ['no','cancel'])) {
                    $this->clearFlow();
                    return 'Sin problema, dejé el pedido sin confirmar. ¿Deseas agregar o cambiar algo?';
                }
                return 'Por favor responde *sí* para confirmar o *no* para cancelar.';
        }

        $this->clearFlow();
        return 'Continuemos. ¿Qué deseas pedir?';
    }

    private function summaryMessage(): string
    {
        $cart = $this->getActiveCart(request());
        $items = $cart ? CartItem::with('product')->where('cart_id',$cart->id)->get() : collect();

        $st = $this->getChatState()->data ?? [];
        $lines = $items->map(fn($i)=> "{$i->quantity} x {$i->product->name}")->implode(', ');
        $total = $items->reduce(fn($a,$i)=> $a + ($i->price * $i->quantity), 0);

        $pay = $st['payment_method'] ?? 'PENDIENTE';
        $ref = $st['payment_reference'] ?? '—';

        return "Resumen: {$lines}. Total: $".number_format($total,2).".
Nombre: ".($st['name']??'—')."
Teléfono: ".($st['phone']??'—')."
Dirección: ".($st['address']??'—')."
Pago: {$pay}".(in_array($pay,['transferencia','pago_movil','zelle'])?" (Ref: {$ref})":"");
    }

    private function createOrderFromChatCash(): string
    {
        $cart = $this->getActiveCart(request());
        if (!$cart) return 'No encuentro un carrito activo. Agrega productos y lo confirmamos.';

        $items = CartItem::where('cart_id', $cart->id)->get();
        if ($items->isEmpty()) return 'Tu carrito está vacío. ¿Deseas agregar productos?';

        $st = $this->getChatState()->data ?? [];

        $order = $this->orderService->createOrderFromCart($cart, [
            'name'              => $st['name'] ?? 'Cliente web',
            'phone'             => $st['phone'] ?? '',
            'shipping_address'  => $st['address'] ?? 'PENDIENTE',
            'payment_method'    => 'cash',
            'payment_reference' => null,
            'notes'             => 'Creado desde chatbot (efectivo)',
            'email'             => $st['email'] ?? null,
            'deduct_now'        => true,
        ]);

        $orderItems = OrderItem::where('order_id', $order->id)->with('product')->get();
        $summary = $this->buildOrderSummaryText($order, $orderItems);

        try {
            $msg = "¡Gracias por tu pedido!\n\n{$summary}\n\n"
                 . "Método: EFECTIVO.\n"
                 . "Un administrador confirmará tu orden y coordinará la entrega.";
            $this->whatsapp->sendCustomMessage($order->phone, $msg);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp (efectivo) falló: '.$e->getMessage(), ['order_id' => $order->id]);
        }

        return "¡Pedido #{$order->id} creado! Te contactaremos al {$order->phone}. ¡Gracias!";
    }

    private function ensureOrderFromChat(): Order
    {
        $cart = $this->getActiveCart(request());
        if (!$cart) throw new \RuntimeException('No hay carrito activo');

        $state = $this->getChatState()->data ?? [];

        if (!empty($state['order_id'])) {
            return Order::findOrFail((int)$state['order_id']);
        }

        $order = $this->orderService->createOrderFromCart($cart, [
            'name'              => $state['name'] ?? 'Cliente web',
            'phone'             => $state['phone'] ?? '',
            'shipping_address'  => $state['address'] ?? 'PENDIENTE',
            'payment_method'    => $state['payment_method'] ?? 'transfer',
            'payment_reference' => $state['payment_reference'] ?? null,
            'notes'             => 'Creado desde chatbot',
            'email'             => $state['email'] ?? null,
        ]);
        $this->deductStockForOrder($order->id);


        $st = $this->getChatState();
        $st->update(['data' => array_merge($state, ['order_id' => $order->id])]);

        return $order;
    }

    private function markOrderPaidNow(Order $order, ?string $reference = null): Order
{
    // asegurar método de pago si vino del wizard
    $st = $this->getChatState()->data ?? [];
    if (empty($order->payment_method) && !empty($st['payment_method'])) {
        $order->payment_method = $st['payment_method']; // efectivo | transferencia | pago_movil | zelle
    }

    // referencia y estado "completed" para que aparezca el badge verde en Pedidos Recientes
    $order->payment_reference   = $reference ?: $order->payment_reference;
    $order->payment_verified_at = now();
    $order->status              = 'completed';
    $order->save();


    return $order->fresh();
}


    private function getActiveCart(Request $req): ?Cart
    {
        $cookieId = $req->cookie('cart_id');
        if ($cookieId) {
            $cart = Cart::where('id', $cookieId)->where('status', 'open')->first();
            if ($cart) {
                return $cart;
            }
        }

        $sid = session()->getId();
        return Cart::where('session_id', $sid)->where('status', 'open')->first();
    }

    /***
     * 🔧 Cambio AQUÍ: precio unitario con fallback para evitar 0.00 en mensajes/whatsapp
     */
    private function buildOrderSummaryText(Order $order, $items): string
    {
        $lines = [];
        foreach ($items as $it) {
            $name = $it->product->name ?? $it->name ?? ('Producto #' . $it->product_id);
            $qty  = (int) $it->quantity;

            // Fallback: unit_price -> price -> product->price
            $unit = (float) (($it->unit_price ?? 0) ?: ($it->price ?? 0) ?: (optional($it->product)->price ?? 0));

            $lines[] = "- {$name} x{$qty} ($" . number_format($unit, 2) . " c/u)";
        }

        $total = number_format((float) $order->total, 2);

        return "Pedido #{$order->id}\n"
             . ($order->name ?? '') . "\n"
             . "Tel: " . ($order->phone ?? '') . "\n\n"
             . "Detalle:\n" . implode("\n", $lines) . "\n\n"
             . "Total: \${$total}";
    }

    private function bankInstructionsFor(string $method): string
    {
        return match ($method) {
            'zelle'  => "Método seleccionado: Zelle\n\nZelle: orquideadeoro@panaderia.com\nNombre: Panadería Orquídea de Oro",
            'mobile' => "Método seleccionado: Pago Móvil\n\nTel: 0412-1234567\nBanco: Banco de Venezuela\nRIF: J-12345678-9",
            default  => "Método seleccionado: Transferencia\n\nBanco: Banco de Venezuela\nCuenta: 0102-0000-00-0000000000\nTitular: Panadería Orquídea de Oro\nRIF: J-12345678-9",
        };
    }


    private function getOrCreateConversationId(?int $customerId = null): int
    {
        $sid = session()->getId();

        $conv = \App\Models\Conversation::firstOrCreate(
            ['session_id' => $sid, 'state' => 'open'],
            ['customer_id' => $customerId, 'state' => 'open']
        );

        // Si ya existía y no tenía customer_id, lo completamos
        if ($customerId && !$conv->customer_id) {
            $conv->customer_id = $customerId;
            $conv->save();
        }

        return (int) $conv->id;
    }


    private function saveBotMessage(int $conversationId, string $text, array $metadata = []): void
    {
        \App\Models\ConversationMessage::create([
            'conversation_id' => $conversationId,
            'role'            => 'assistant',
            'text'            => $text,
            'metadata'        => $metadata,
        ]);
    }

    /* =========================
       🔹 Intents
       ========================= */
    private function handleAddToCart(array $entities): string
    {
        $sessionId = session()->getId();
        $items = $entities['items'] ?? [];
        if (empty($items)) return '¿Qué producto deseas agregar y cuántas unidades?';

        $added = 0; $missing = [];

        \DB::transaction(function() use ($items,$sessionId,&$added,&$missing) {
            $cart = Cart::firstOrCreate(
                ['session_id'=>$sessionId,'status'=>'open'],
                ['total'=>0]
            );

            foreach ($items as $row) {
                $raw = trim(mb_strtolower($row['name'] ?? ''));
                $qty = max(1, (int)($row['qty'] ?? 1));
                if ($raw==='') continue;

                $candidates = [$raw, rtrim($raw,'s'), preg_replace('/(es)$/u','',$raw)];

                $product = null;
                foreach ($candidates as $cand) {
                    if (!$cand) continue;
                    $words = array_filter(preg_split('/\s+/',$cand), fn($w)=>mb_strlen($w)>=2);
                    $sing = array_map(fn($w)=> rtrim(preg_replace('/es$/u','',$w),'s'), $words);
                    if ($sing) {
                        $q = Product::query();
                        foreach ($sing as $w) $q->whereRaw('LOWER(name) LIKE ?',["%$w%"]);
                        $p=$q->first();
                        if ($p){$product=$p;break;}
                    }
                    $p=Product::whereRaw('LOWER(name)=?',[$cand])->first();
                    if($p){$product=$p;break;}
                    $p=Product::whereRaw('LOWER(name) LIKE ?',["%$cand%"])->first();
                    if($p){$product=$p;break;}
                }

                if(!$product){$missing[]=$raw;continue;}

                $ci=CartItem::firstOrNew([
                    'cart_id'=>$cart->id,
                    'product_id'=>$product->id,
                ]);
                $ci->quantity=($ci->exists?(int)$ci->quantity:0)+$qty;
                $ci->price=$product->price;
                $ci->save();
                $added+=$qty;
            }

            $cart->total=CartItem::where('cart_id',$cart->id)->get()
                ->reduce(fn($a,$i)=>$a+($i->price*$i->quantity),0);
            $cart->save();
        });

        if ($added===0) {
            if ($missing) return 'No pude encontrar: '.implode(', ',$missing).'. ¿Quieres intentar con otros nombres?';
            return 'No pude agregar productos. ¿Podrías repetir nombre y cantidad?';
        }

        if ($missing) return "Añadí {$added} unidad(es). No pude encontrar: ".implode(', ',$missing).". ¿Confirmas o agregas algo más?";
        return "¡Listo! Añadí {$added} unidad(es) a tu carrito. ¿Deseas confirmar tu pedido o agregar algo más?";
    }

private function handleCheckStock(array $entities): string
{
    $text = trim((string) request()->input('text', ''));
    $term = $this->extractProductQuery('CHECK_STOCK', $entities, $text);
    if ($term === '') {
        return '¿De cuál producto quieres saber disponibilidad? Por ejemplo: *¿tienes pan canilla disponible?*';
    }

    // 2) Búsqueda primaria: productos con stock > 0 que hagan match
    $q = Product::query()
        ->inStock() // usa tu scope existente
        ->where(function ($qq) use ($term) {
            $qq->where('name', 'like', "%{$term}%")
               ->orWhere('description', 'like', "%{$term}%");
        })
        ->orderByDesc('updated_at')
        ->limit(10);

    $matches = $q->get(['id','name','stock','price']);

    // 3) Si no hay resultados, relajar por tokens
    if ($matches->isEmpty()) {
        $pieces = array_filter(preg_split('~\s+~u', $term));
        if (!empty($pieces)) {
            $qq = Product::query()->inStock();
            foreach ($pieces as $p) {
                $p = trim($p);
                if ($p === '') continue;
                $qq->where('name', 'like', "%{$p}%");
            }
            $matches = $qq->orderByDesc('updated_at')
                          ->limit(10)
                          ->get(['id','name','stock','price']);
        }
    }

    // 4) Respuesta
    if ($matches->isEmpty()) {
        return "No tengo coincidencias para *{$term}*. ¿Quieres que te muestre productos similares?";
    }

    $top = $matches->first();
    $isClear = (mb_stripos($top->name ?? '', $term) !== false);

    if ($isClear) {
        $precio = number_format((float)($top->price ?? 0), 2, ',', '.');
        return "Sí, *{$top->name}* está disponible.\nStock: *{$top->stock}* unidades\nPrecio: *{$precio}*";
    }

    $lines = [];
    foreach ($matches->take(3) as $m) {
        $precio = number_format((float)($m->price ?? 0), 2, ',', '.');
        $lines[] = "• *{$m->name}* — stock: {$m->stock} unidades, precio: {$precio}";
    }

    return "Encontré estas opciones relacionadas con *{$term}*:\n"
         . implode("\n", $lines)
         . "\n\n¿Cuál te interesa?";
}

/**
 * Muestra el historial de compras del usuario (últimos pedidos) o, si se
 * provee order_id en entities, devuelve el detalle de esa orden.
 *
 * Uso desde NLU:
 * - Intent que liste historial: llamar a this->handleOrderHistory($entities)
 * - Intent que pida detalles: incluir entidad order_id para que devuelva el detalle
 */
private function handleOrderHistory(array $entities): string
{
    $uid = auth()->id();
    if (!$uid && request()->bearerToken()) {
        $pat = PersonalAccessToken::findToken(request()->bearerToken());
        if ($pat) $uid = (int) $pat->tokenable_id;
    }

    if (!$uid) {
        return 'Debes iniciar sesión para ver tu historial de compras.';
    }

    // Si la NLU ya nos dio un order_id, devolvemos detalle directo
    $orderId = null;
    if (!empty($entities['order_id'])) {
        $orderId = (int) $entities['order_id'];
    } elseif (!empty($entities['id'])) {
        $orderId = (int) $entities['id'];
    }

    if ($orderId) {
        $order = Order::where('id', $orderId)->where('user_id', $uid)->first();
        if (!$order) {
            return "No encontré la compra con ID {$orderId} en tu historial.";
        }

        $items = OrderItem::where('order_id', $order->id)->with('product')->get();
        return $this->buildOrderSummaryText($order, $items);
    }

    // Listado breve de los últimos pedidos
    $orders = Order::where('user_id', $uid)
        ->orderByDesc('id')
        ->limit(8)
        ->get();

    if ($orders->isEmpty()) {
        return 'No tengo pedidos registrados para tu cuenta.';
    }

    // Mapear estados y métodos de pago a etiquetas más amigables en español
    $statusMap = [
        'paid'        => 'pagada',
        'cash_on_delivery' => 'pagada (efectivo)',
        'pending'     => 'pendiente',
        'processing'  => 'en proceso',
        'completed'   => 'completada',
        'cancelled'   => 'cancelada',
        'refunded'    => 'reembolsada',
        // agrega más mapeos si los necesitas
    ];

    $paymentMap = [
        'cash_on_delivery' => 'efectivo',
        'cash'             => 'efectivo',
        'transfer'         => 'transferencia',
        'mobile'           => 'pago móvil',
        'zelle'            => 'zelle',
        // agrega más mapeos si los necesitas
    ];

    foreach ($orders as $o) {
        $o->status = $statusMap[$o->status ?? ''] ?? ($o->status ?? '');
        if (isset($o->payment_method)) {
            $o->payment_method = $paymentMap[$o->payment_method ?? ''] ?? ($o->payment_method ?? '');
        }
    }

    $lines = [];
    foreach ($orders as $o) {
        $statusInfo = method_exists($this, 'formatStatusForAdmin') ? $this->formatStatusForAdmin($o->status ?? '') : ['label' => ($o->status ?? '')];
        $code = sprintf('ORD-%04d', $o->id);
        $date = $o->created_at ? $o->created_at->format('Y-m-d') : '';
        $total = number_format((float) $o->total, 2, ',', '.');
        $lines[] = "{$o->id} ({$code}) — {$date} — {$statusInfo['label']} — \${$total}";
    }

    // Guardamos los últimos ids mostrados en el flujo conversacional para validar la respuesta del usuario
    $ids = $orders->pluck('id')->toArray();
    $this->setStep('ask_order_id', ['last_order_ids' => $ids]);

    return "Aquí están tus últimas compras:\n" . implode("\n", $lines) . "\n\nSi quieres saber los detalles de alguna de estas compras indícame el ID (por ejemplo: 123).";
}

private function handleAnalyticsReport(array $nlu): string
{
    // Texto original para detectar subtipo
    $text = (string) request()->input('text', '');
    $t = mb_strtolower($text);

    // Rango de fechas usando tu helper DateRanges
    [$from, $to] = DateRanges::parse($text);
    $fromS = $from->toDateTimeString();
    $toS   = $to->toDateTimeString();
    $rang  = $this->prettyRange($from, $to);


    // Subtipos por palabra clave
    if (str_contains($t, 'pico') || str_contains($t, 'hora')) {
        $data = $this->analytics->peakHours($fromS, $toS);
        if (empty($data) || count($data) === 0) {
            return "⏰ *Horas pico* - $rang\nNo hay datos en el rango.";
        }
        $lines = collect($data)->map(fn($r) =>
            sprintf("- %02d:00 → %d pedidos (Bs. %.2f)", $r->hour, $r->orders, $r->revenue)
        )->join("\n");
        return "⏰ *Horas pico* - $rang\n".$lines;
    }

    if (str_contains($t, 'market') || str_contains($t, 'carrito')) {
        $rules = $this->analytics->marketBasket($fromS, $toS, 0.02, 0.1, 20);
        if (empty($rules)) {
            return "🧺 *Market basket* - $rang\nNo hay suficientes co-ocurrencias para generar reglas.";
        }
        $lines = collect($rules)->map(fn($r) =>
            "- Si compran *{$r['antecedent']}*, recomienda *{$r['consequent']}* "
            ."(conf. ".number_format($r['confidence']*100,1)."%, sup. ".number_format($r['support']*100,1)."%)"
        )->join("\n");
        return "🧺 *Market basket* - $rang\n".$lines;
    }

    if (str_contains($t, 'rfm')) {
        $rfm = $this->analytics->rfm($toS);
        if (empty($rfm)) {
            return "👥 *RFM* - $rang\nNo hay clientes con compras registradas.";
        }
        $segCounts = collect($rfm)->groupBy('segment')->map->count()->sortDesc()->take(8);
        $lines = $segCounts->map(fn($c,$seg)=>"- $seg → $c clientes")->join("\n");
        return "👥 *RFM* - $rang\nSegmentos más frecuentes:\n{$lines}\n\n(333 = mejores; 111 = inactivos)";
    }

    if (preg_match('/anomal|problem|ca[ií]d|baj[ao]s?|alert|desv[ií]o|rar[oa]s?/u', $t)) {

    $res = $this->analytics->dailyAnomalies($fromS, $toS, 2.0); // z=2.0
    $rang = $this->prettyRange($from, $to);

    if (empty($res) || empty($res['anomalies'])) {
        return "📈 *Anomalías de ventas* — $rang\nNo detecté picos ni caídas fuera de lo normal.";
    }

    $anoms = collect($res['anomalies'])->map(function($a){
        $tipo = $a['type'] === 'spike' ? '📈 pico' : '📉 caída';
        $rev  = number_format((float)$a['revenue'], 2);
        return "- {$a['date']}: {$tipo} (Bs. {$rev}, z={$a['z']})";
    })->join("\n");

    $mean = number_format((float)($res['mean'] ?? 0), 2);
    $sd   = number_format((float)($res['sd'] ?? 0), 2);

    return "📈 *Anomalías de ventas* — $rang\n"
         . $anoms
         . "\nPromedio diario: Bs. {$mean} (σ={$sd})";
}

    // Por defecto: Top productos más vendidos
    $top = $this->analytics->topProducts($fromS, $toS, 10);
    if (empty($top) || count($top) === 0) {
        return "📦 *Top productos* - $rang\nNo hay ventas en el rango.";
    }
    $lines = collect($top)->map(fn($r) =>
        "- {$r->name}: {$r->units} uds (Bs. ".number_format($r->revenue,2).")"
    )->join("\n");
    return "📦 *Top productos* - $rang\n".$lines;
}


/**
 * Intenta resolver una respuesta numérica cuando el wizard está en 'ask_order_id'.
 * Se puede invocar desde handleWizardStep si añades el case correspondiente:
 *   case 'ask_order_id': return $this->handleOrderIdReply($text); 
 */
private function handleOrderIdReply(string $text): string
{
    $st = $this->getChatState()->data ?? [];
    $ids = $st['last_order_ids'] ?? [];

    $candidate = (int) filter_var($text, FILTER_SANITIZE_NUMBER_INT);
    if ($candidate <= 0) {
        return 'Indica el ID numérico de la compra (ej: 123).';
    }

    // Si mostramos recientemente una lista, validamos que el ID esté en ella
    if (!empty($ids) && !in_array($candidate, $ids, true)) {
        // permitimos igualmente buscar por seguridad, pero avisamos
        $order = Order::where('id', $candidate)->where('user_id', auth()->id())->first();
        if (!$order) {
            return "No encontré la compra {$candidate} en tu historial reciente. Revisa el ID o pide que te muestre nuevamente la lista.";
        }
    } else {
        $order = Order::where('id', $candidate)->where('user_id', auth()->id())->first();
        if (!$order) {
            return "No encontré la compra con ID {$candidate}. Verifica el número y vuelve a intentarlo.";
        }
    }

    $items = OrderItem::where('order_id', $order->id)->with('product')->get();
    // cerramos el flujo de solicitud de ID
    $this->clearFlow();

    return $this->buildOrderSummaryText($order, $items);
}
/**
 * Normaliza el término de producto según el intent (CHECK_STOCK / ASK_PRICE).
 */
private function extractProductQuery(string $intent, array $entities, string $text): string
{
    $raw = trim((string)($entities['query'] ?? ''));
    if ($raw === '') $raw = $text;

    $t = ' ' . mb_strtolower($raw) . ' ';
    // quitar signos
    $t = preg_replace('~[?¿¡!.,;:"]+~u', ' ', $t);
    // artículos y conectores
    $t = preg_replace('~\b(el|la|los|las|de|del|al|a|un|una|unos|unas)\b~u', ' ', $t);

    // triggers comunes
    $common = ['hay','tiene','tienes','tienen','disponible','disponibles','stock','queda','quedan'];

    // triggers de precio
    $price  = ['precio','cuanto','cuánto','vale','cuesta','valor'];

    $all = $intent === 'ASK_PRICE' ? array_merge($common, $price) : $common;

    $pattern = '~\b(' . implode('|', array_map('preg_quote', $all)) . ')\b~u';
    $t = preg_replace($pattern, ' ', $t);

    // compactar espacios
    $t = trim(preg_replace('~\s+~u', ' ', $t));

    return (mb_strlen($t) >= 2) ? $t : '';
}




    private function handleRecommend(): string
    {
        return 'Hoy te recomendamos probar nuestra torta tres leches 😋.';
    }

    private function handleConfirmOrder(): string
    {
        $missing = $this->needFieldsForOrder();
        if (in_array('cart',$missing)) return 'No encuentro un carrito activo. Agrega productos y lo confirmamos.';
        if (in_array('phone',$missing)) { $this->setStep('ask_phone'); return 'Para confirmar, ¿me compartes tu teléfono?'; }
        if (in_array('name',$missing)) { $this->setStep('ask_name'); return '¿A nombre de quién registramos el pedido?'; }
        if (in_array('address',$missing)) { $this->setStep('ask_address'); return '¿Cuál es la dirección de entrega (o Retiro en tienda)?'; }
        if (in_array('payment_method',$missing)) { $this->setStep('ask_payment_method'); return '¿Cómo deseas pagar? (efectivo / transferencia / pago móvil / zelle)'; }
        if (in_array('payment_reference',$missing)) { $this->setStep('ask_payment_reference'); return "Indica el número de referencia o últimos 4 dígitos.\n(Al enviarla, cerraremos tu pedido automáticamente)"; }

        $this->setStep('confirm_summary');
        return $this->summaryMessage()." ¿Confirmas tu pedido? (sí/no)";
    }
private function handleListAvailableProducts(array $entities, array $nlu): string
{
    $total = \App\Models\Product::query()->inStock()->count();

    // Ajusta si tu catálogo vive en otra ruta, p.ej. url('/productos') o url('/tienda')
    $catalogUrl = url('/');

    if ($total <= 0) {
        return "Por ahora no tengo productos con stock. Si buscas algo en específico, dímelo y te confirmo en minutos.";
    }

    // Usa Markdown: el widget ya parsea [label](url) y mostrará solo “Aquí” clickeable.
    return "Tenemos *{$total} productos* disponibles ahora mismo. 🛒\n"
         . "Es más cómodo verlos en el catálogo: ({$catalogUrl})\n\n"
         . "Si quieres, te digo la disponibilidad de un producto específico (ej.: *¿tienes pan canilla?*) o te muestro por categoría.";
}
private function handleAskPrice(array $entities): string
{
    $text = trim((string) request()->input('text', ''));
    $term = $this->extractProductQuery('ASK_PRICE', $entities, $text);

    if ($term === '') {
        return '¿De cuál producto quieres saber el precio?';
    }

    $matches = \App\Models\Product::query()
        // si quieres incluir sin stock, quita esta línea
        ->where('stock', '>', 0)
        ->where(function($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        })
        ->orderByDesc('updated_at')
        ->limit(5)
        ->get(['name','price','stock']);

    if ($matches->isEmpty()) {
        return "No tengo coincidencias para *{$term}*. ¿Quieres que te muestre productos similares?";
    }

    if ($matches->count() === 1) {
        $p = $matches->first();
        $precio = number_format((float) $p->price, 2, ',', '.');
        return "El *{$p->name}* tiene un precio de *{$precio}*"
             . ($p->stock > 0 ? " y hay *{$p->stock}* unidades disponibles." : ".");
    }

    $lines = [];
    foreach ($matches as $m) {
        $precio = number_format((float) $m->price, 2, ',', '.');
        $lines[] = "• *{$m->name}* — {$precio}";
    }
    return "Encontré varios productos relacionados con *{$term}*:\n" . implode("\n", $lines);
}
/**
 * Descuenta stock de los productos de una orden de forma segura.
 */
private function deductStockForOrder(int $orderId): void
{
    \DB::transaction(function () use ($orderId) {
        $items = OrderItem::where('order_id', $orderId)->get();
        foreach ($items as $it) {
            // lockForUpdate para evitar carreras
            $p = Product::where('id', $it->product_id)->lockForUpdate()->first();
            if (!$p) continue;

            $p->stock = max(0, (int)$p->stock - (int)$it->quantity);
            $p->save();
        }
    });
}


private function prettyRange(\Carbon\Carbon $from, \Carbon\Carbon $to): string
{
    $fromD = $from->copy(); $toD = $to->copy();

    // atajos
    $today = now()->startOfDay();
    $yest  = now()->subDay()->startOfDay();
    if ($fromD->isSameDay($today) && $toD->isSameDay($today)) {
        return 'hoy';
    }
    if ($fromD->isSameDay($yest) && $toD->isSameDay($yest)) {
        return 'ayer';
    }

    // semana actual
    if ($fromD->isSameDay(now()->startOfWeek()) && $toD->isSameDay(now()->endOfWeek())) {
        return 'esta semana';
    }
    // semana pasada
    if ($fromD->isSameDay(now()->subWeek()->startOfWeek()) && $toD->isSameDay(now()->subWeek()->endOfWeek())) {
        return 'la semana pasada';
    }
    // mes actual
    if ($fromD->isSameDay(now()->startOfMonth()) && $toD->isSameDay(now()->endOfMonth())) {
        return 'este mes';
    }
    // mes pasado
    if ($fromD->isSameDay(now()->subMonth()->startOfMonth()) && $toD->isSameDay(now()->subMonth()->endOfMonth())) {
        return 'el mes pasado';
    }

    // fallback: 1–30 sep 2025
    $mes = [1=>'ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $fmt = fn($d) => $d->day.' '.$mes[$d->month].' '.$d->year;
    // si mismo mes/año, usa 1–30 sep 2025
    if ($fromD->month === $toD->month && $fromD->year === $toD->year) {
        return $fromD->day.'–'.$toD->day.' '.$mes[$toD->month].' '.$toD->year;
    }
    return $fmt($fromD).' a '.$fmt($toD);
}


}
