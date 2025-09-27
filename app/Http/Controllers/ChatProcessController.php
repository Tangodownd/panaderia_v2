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

class ChatProcessController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private WhatsAppService $whatsapp
    ) {}

    public function process(Request $req, AiClient $ai)
    {
        $sessionId = session()->getId();
        $text = trim((string) $req->input('text', ''));

        $conversationId = $this->getOrCreateConversationId();

        // 1) Wizard activo
        $state = $this->getChatState();
        if ($state->step) {
            $reply = $this->handleWizardStep($state->step, $text, $conversationId);
            return response()->json([
                'reply' => $reply,
                'conversation_id' => $conversationId,
            ]);
        }

        // 2) IA (opcional)
        $context = [
            'session_id'  => $sessionId,
            'cart_cookie' => $req->cookie('cart_id'),
            'user_id'     => auth()->id(),
        ];
        $nlu = $ai->interpret($text, $context);

        $intent   = $nlu['intent']   ?? 'SMALL_TALK';
        $entities = $nlu['entities'] ?? [];

        $reply = $this->routeIntent($intent, $entities, $nlu);

        return response()->json([
            'reply' => $reply,
            'conversation_id' => $conversationId,
        ]);
    }

    private function routeIntent(string $intent, array $entities, array $nlu): string
    {
        return match ($intent) {
            'CREATE_ORDER', 'ADD_TO_CART' => $this->handleAddToCart($entities),
            'CHECK_STOCK'                  => $this->handleCheckStock($entities),
            'RECOMMEND'                    => $this->handleRecommend(),
            'CONFIRM_ORDER'                => $this->handleConfirmOrder(),
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

        if (!empty($st['payment_method']) && in_array($st['payment_method'], ['transferencia','pago_movil','zelle'])) {
            if (empty($st['payment_reference'])) $need[] = 'payment_reference';
        }
        return $need;
    }

    private function normalizePaymentMethod(string $text): ?string
    {
        $t = Str::lower($text);
        $t = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $t);

        if (Str::contains($t, ['efectivo','cash'])) return 'efectivo';
        if (Str::contains($t, ['zelle'])) return 'zelle';
        if (Str::contains($t, ['pago movil','pagomovil','movil','mobile'])) return 'pago_movil';
        if (Str::contains($t, ['transfer','transferencia'])) return 'transferencia';

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

                if (in_array($pm, ['transferencia','pago_movil','zelle'])) {
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
            'payment_method'    => 'efectivo',
            'payment_reference' => null,
            'notes'             => 'Creado desde chatbot (efectivo)',
            'email'             => $st['email'] ?? null,
            'deduct_now'        => false,
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
            'payment_method'    => $state['payment_method'] ?? 'transferencia',
            'payment_reference' => $state['payment_reference'] ?? null,
            'notes'             => 'Creado desde chatbot',
            'email'             => $state['email'] ?? null,
        ]);

        $st = $this->getChatState();
        $st->update(['data' => array_merge($state, ['order_id' => $order->id])]);

        return $order;
    }

    private function markOrderPaidNow(Order $order, ?string $reference = null): Order
    {
        $order->payment_reference   = $reference ?: $order->payment_reference;
        $order->status              = 'paid';
        $order->payment_verified_at = now();
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
            'zelle' => "Método seleccionado: Zelle\n\nZelle: correo@ejemplo.com\nNombre: Panadería Orquídea de Oro",
            'pago_movil' => "Método seleccionado: Pago Móvil\n\nTel: 0412-1234567\nBanco: Bxxxx\nRIF: J-12345678-9",
            default => "Método seleccionado: Transferencia\n\nBanco: Bxxxx\nCuenta: 0102-0000-00-0000000000\nTitular: Panadería Orquídea de Oro\nRIF: J-12345678-9",
        };
    }

    private function getOrCreateConversationId(): int
    {
        $sid     = session()->getId();
        $custId  = auth()->id();

        $conv = \App\Models\Conversation::firstOrCreate(
            ['session_id' => $sid, 'state' => 'open'],
            ['customer_id' => $custId, 'state' => 'open']
        );

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
    // 1) Extraer el término
    $text = trim((string) request()->input('text', ''));
    $term = $this->extractStockQuery($entities, $text);

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
 * Normaliza el término para preguntas de disponibilidad.
 * - Usa entities['query'] si viene, pero SIEMPRE limpia stops (hay, disponible, stock, etc.).
 * - Si no viene, limpia el texto original.
 */
private function extractStockQuery(array $entities, string $text): string
{
    // 1) Punto de partida: entities.query > texto
    $raw = trim((string)($entities['query'] ?? ''));
    if ($raw === '') $raw = $text;

    // 2) normalizar
    $t = ' ' . mb_strtolower($raw) . ' ';
    // quitar signos
    $t = preg_replace('~[?¿¡!.,;:"]+~u', ' ', $t);
    // quitar conectores tipo "de"
    $t = preg_replace('~\bde(l|la|los|las)?\b~u', ' ', $t);

    // 3) quitar palabras gatillo en cualquier posición
    $triggers = [
        'hay','tiene','tienes','tienen',
        'disponible','disponibles',
        'stock','queda','quedan','queda?','quedan?'
    ];
    $pattern = '~\b(' . implode('|', array_map('preg_quote', $triggers)) . ')\b~u';
    $t = preg_replace($pattern, ' ', $t);

    // 4) compactar espacios
    $t = trim(preg_replace('~\s+~u', ' ', $t));

    // 5) evitar términos vacíos o demasiado genéricos
    if ($t === '' || mb_strlen($t) < 2) return '';

    return $t;
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
}
