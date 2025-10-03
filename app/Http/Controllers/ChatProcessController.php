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


class ChatProcessController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private WhatsAppService $whatsapp,

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
                // En este punto mostramos también un resumen y explicamos opciones siguientes.
                $summary = $this->summaryMessage();
                return $summary . "\n\n¿Cómo deseas pagar? (efectivo / transferencia / pago móvil / zelle)\n\n".
                    "También puedes escribir: *quitar X*, *agregar Y*, *cancelar pedido* o *ayuda*.";

            case 'ask_payment_method':
                $tLower = Str::lower($text);
                // Opción 3: cancelar
                if (Str::contains($tLower, ['cancel','cancelo','cancelar'])) {
                    $this->clearFlow();
                    return 'Pedido cancelado ✅. Puedes empezar otro cuando quieras.';
                }
                // Opción 2: quitar producto (patrón: quitar X / eliminar X / remover X)
                if (preg_match('/\b(quita|quitar|elimina|eliminar|remueve|remover)\s+(.{2,60})/iu', $text, $mm)) {
                    $prodTxt = trim($mm[2]);
                    $removed = $this->removeProductApprox($prodTxt);
                    $summary = $this->summaryMessage();
                    if ($removed) {
                        return "Eliminé *{$removed}* del carrito.\n\n".$summary."\n¿Método de pago? (efectivo / transferencia / pago móvil / zelle)";
                    }
                    return "No encontré ese producto en tu carrito. Intenta con el nombre exacto.\n\n".$summary;
                }
                // Opción 2 (b): agregar algo más aquí mismo: detectamos verbo agregar + cantidad/nombre
                if (preg_match('/\b(agrega|añade|agregar|añadir|suma|pon)\b/iu', $tLower)) {
                    // Enviamos la frase al flujo normal de addToCart reutilizando handleAddToCart
                    // Para no romper el step actual, solo procesamos y regresamos a ask_payment_method
                    $fakeEntities = ['items'=>[]];
                    $addReply = $this->handleAddToCart($fakeEntities); // parseará fallback inline
                    // Forzamos regresar al mismo paso (si no entró en confirmación)
                    $this->setStep('ask_payment_method', array_merge($this->getChatState()->data ?? [], []));
                    if (Str::startsWith($addReply, 'Perfecto') || Str::contains($addReply, 'Indícame producto')) {
                        // No agregó nada todavía
                        return $addReply;
                    }
                    return $addReply."\n\n".$this->summaryMessage()."\n¿Método de pago ahora? (efectivo / transferencia / pago móvil / zelle)";
                }
                // Opción 4: otra pregunta (si detectamos palabras clave de ayuda / horario / ubicación, devolvemos al mini IA)
                if (Str::contains($tLower, ['ayuda','horario','ubicacion','ubicación','metodos','pago','donde'])) {
                    return 'Respondo primero tu duda: escribe tu pregunta concreta o si prefieres continúa indicando método de pago.';
                }

                // Opción 1: método de pago
                $pm = $this->normalizePaymentMethod($text);
                if (!$pm) {
                    return 'Método no reconocido. Opciones: efectivo, transferencia, pago móvil o zelle. También puedes: *quitar X*, *agregar Y*, *cancelar pedido*.';
                }
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

    /**
     * Elimina un producto (match aproximado) del carrito activo.
     */
    private function removeProductApprox(string $needle): ?string
    {
        $origNeedle = trim($needle);
        $needle = $this->normalizeProdFragment($origNeedle);
        if ($needle === '') return null;

        $cart = $this->getActiveCart(request());
        if (!$cart) return null;
        $items = CartItem::where('cart_id',$cart->id)->with('product')->get();

        // Genera variantes (singular básico)
        $variants = [$needle];
        if (preg_match('/s$/u', $needle) && mb_strlen($needle) > 3) {
            $variants[] = rtrim($needle,'s');
        }
        if (preg_match('/es$/u',$needle) && mb_strlen($needle) > 4) {
            $variants[] = preg_replace('/es$/u','',$needle);
        }
        $variants = array_unique($variants);

        // Stopwords para tokens
        $stop = ['el','la','los','las','un','una','unos','unas','de','del','al'];

        foreach ($items as $ci) {
            $pName = $ci->product->name;
            $norm = $this->normalizeProdFragment($pName);

            // 1. coincidencia exacta con alguna variante
            foreach ($variants as $v) {
                if ($norm === $v) {
                    $nm = $pName; $ci->delete(); $this->recomputeCartTotal($cart); return $nm; }
            }
            // 2. contains
            foreach ($variants as $v) {
                if ($v !== '' && str_contains($norm, $v)) {
                    $nm = $pName; $ci->delete(); $this->recomputeCartTotal($cart); return $nm; }
            }
            // 3. tokens subset (todas las tokens del needle están en el nombre)
            $needleTokens = array_values(array_filter(preg_split('/\s+/u',$needle), fn($t)=> $t!=='' && !in_array($t,$stop,true)));
            if ($needleTokens) {
                $all = true;
                foreach ($needleTokens as $tk) {
                    if (!str_contains($norm,$tk)) { $all=false; break; }
                }
                if ($all) { $nm = $pName; $ci->delete(); $this->recomputeCartTotal($cart); return $nm; }
            }
        }
        return null;
    }

    private function normalizeProdFragment(string $s): string
    {
        $s = mb_strtolower($s);
        $s = strtr($s,[ 'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n' ]);
        $s = preg_replace('/^(el|la|los|las|un|una|unos|unas)\s+/u','',$s);
        $s = preg_replace('/\s+/u',' ', $s);
        $s = trim($s, " -_:");
        return $s;
    }

    private function recomputeCartTotal(Cart $cart): void
    {
        $cart->total = CartItem::where('cart_id',$cart->id)->get()->reduce(fn($a,$i)=>$a+($i->price*$i->quantity),0);
        $cart->save();
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
        $rawUserText = mb_strtolower(trim((string) request()->input('text','')));

        // Fallback interno: si la NLU no extrajo items, intentamos parsear expresiones tipo
        // "agrega dos cachitos", "añade 3 golfeados", "pon 1 pan campesino".
        if (empty($items) && $rawUserText !== '') {
            $items = $this->parseInlineAddExpression($rawUserText);
        }
        // Segundo intento: parser libre (cuando la frase incluye "quisiera pedir...")
        if (empty($items) && $rawUserText !== '') {
            $items = $this->extractItemsFromFreeForm($rawUserText);
        }
        // Palabras genéricas que no representan realmente un producto
        $genericOrderWords = ['pedido','pedidos','orden','ordenes','compra','compras','mi pedido','un pedido','una orden'];

        // Normalizar y filtrar placeholders
        $filtered = [];
        foreach ($items as $it) {
            $nameNorm = trim(mb_strtolower($it['name'] ?? ''));
            // Si el nombre coincide EXACTO con una palabra genérica lo ignoramos
            if ($nameNorm === '' ) continue;
            $plain = preg_replace('/\s+/', ' ', $nameNorm);
            if (in_array($plain, $genericOrderWords, true)) continue;
            $filtered[] = $it;
        }

        // Si tras filtrar no queda nada, interpretamos que el usuario solo expresó la intención de iniciar pedido
        if (empty($filtered)) {
            $state = $this->getChatState();
            if (($state->step ?? null) !== 'ask_first_item') {
                $this->setStep('ask_first_item', []);
                return 'Perfecto 👍 Empecemos tu pedido. Dime producto y cantidad. Ej: *agrega 2 cachitos* o *1 pan campesino*';
            }
            return 'Indícame producto y cantidad. Ej: *2 golfeados*, *agrega 1 pan campesino*';
        }

        // Trabajamos con los ítems reales filtrados
        $items = $filtered;

        $added = 0; $missing = [];

        \DB::transaction(function() use ($items,$sessionId,&$added,&$missing) {
            $cart = Cart::firstOrCreate(
                ['session_id'=>$sessionId,'status'=>'open'],
                ['total'=>0]
            );

            foreach ($items as $row) {
                $raw = trim(mb_strtolower($row['name'] ?? ''));
                $raw = $this->stripPoliteSuffixes($raw);
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

                if(!$product){
                    // Intento de coincidencia aproximada (levenshtein sobre candidatos)
                    $approx = $this->findApproxProduct($raw);
                    if ($approx) {
                        $product = $approx;
                    }
                }
                if(!$product){$missing[]=$raw;continue;}

                // Validación de stock al momento de agregar al carrito
                $available = (int)($product->stock ?? 0);
                if ($available <= 0) {
                    $missing[] = $raw.' (sin stock)';
                    continue;
                }
                if ($available < $qty) {
                    // Ajustar a lo disponible
                    $qty = $available;
                }
                if ($qty <= 0) { $missing[] = $raw.' (sin stock)'; continue; }

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
            if ($missing) {
                $missingStock = [];$missingNotFound=[];
                foreach ($missing as $m) {
                    if (str_contains($m,'(sin stock)')) {
                        $missingStock[] = trim(str_replace('(sin stock)','',$m));
                    } else {
                        $missingNotFound[] = $m;
                    }
                }
                $parts = [];
                if ($missingNotFound) {
                    $parts[] = 'No pude identificar: '.implode(', ', $missingNotFound);
                }
                if ($missingStock) {
                    $parts[] = 'Sin stock ahora mismo de '.implode(', ', $missingStock);
                }
                $msg = implode('. ', $parts).'. ';
                return $msg;
            }
            return 'No se agregó nada. Indica cantidad y producto. Ej: *2 cachitos* o *agrega 1 golfeado*';
        }

        // Si estábamos en el paso inicial, salimos del modo 'ask_first_item'
        $state = $this->getChatState();
        if (($state->step ?? null) === 'ask_first_item') {
            $this->setStep(null); // limpiamos el step ya que ya hay un ítem real
        }

        // Preparar mensaje base
        if ($missing) {
            $missingStock = [];$missingNotFound=[];
            foreach ($missing as $m) {
                if (str_contains($m,'(sin stock)')) {
                    $missingStock[] = trim(str_replace('(sin stock)','',$m));
                } else {
                    $missingNotFound[] = $m;
                }
            }
            $detail = [];
            if ($missingNotFound) $detail[] = 'no identificado: '.implode(', ', $missingNotFound);
            if ($missingStock) $detail[] = 'sin stock: '.implode(', ', $missingStock);
            $detailStr = $detail ? (' ('.implode(' | ', $detail).')') : '';
            $base = "Añadí {$added} unidad(es).".$detailStr.". ¿Confirmas o agregas algo más?";
        } else {
            $base = "¡Listo! Añadí {$added} unidad(es) a tu carrito. ¿Deseas confirmar tu pedido o agregar algo más?";
        }

        // Añadir recomendaciones SI: usuario autenticado, tiene historial y hay sugerencias que no estén en el carrito
        // Se evita repetir si ya mostramos para la misma "huella" de carrito (productos ordenados) en la sesión actual.
        $uid = auth()->id();
        if (!$uid && request()->bearerToken()) {
            $pat = PersonalAccessToken::findToken(request()->bearerToken());
            if ($pat) $uid = (int) $pat->tokenable_id;
        }
        if ($uid) {
            // ¿Tiene historial previo (al menos 1 orden completada o pagada)?
            $hasHistory = Order::where('user_id',$uid)->whereIn('status',[ 'paid','completed','processing','cash_on_delivery' ])->exists();
            if ($hasHistory) {
                $cart = $this->getActiveCart(request());
                $cartIds = $cart ? CartItem::where('cart_id',$cart->id)->pluck('product_id')->map(fn($v)=>(int)$v)->all() : [];
                $fingerprint = implode('-', collect($cartIds)->sort()->all());
                $sessionShown = session()->get('rec_shown_fingerprints', []);

                // Solo recomendar si no se ha mostrado ya para esta misma combinación de productos
                if (!in_array($fingerprint, $sessionShown, true)) {
                    // 1) Recomendaciones basadas en co-ocurrencia (market basket)
                    $basket = $this->coOccurrenceRecommendations($cartIds, 6);
                    // 2) Fallback: historial personal
                    if (empty($basket)) {
                        $personal = $this->personalTopProducts($uid, 12); // reutiliza método existente
                    } else {
                        $personal = [];
                    }

                    $candidates = collect(array_merge($basket, $personal));
                    if ($candidates->isNotEmpty()) {
                        // Filtrar: quitar ya en carrito, quitar sin stock
                        $inStockIds = $this->inStockProductIdMap($candidates->pluck('product_id')->all());
                        $suggest = $candidates
                            ->reject(fn($p)=> in_array($p['product_id'], $cartIds, true))
                            ->filter(fn($p)=> isset($inStockIds[$p['product_id']]))
                            ->unique('product_id')
                            ->take(3)
                            ->pluck('name')
                            ->all();
                        if (!empty($suggest)) {
                            $list = '• '.implode("\n• ", $suggest);
                            $base .= "\n\nTal vez también te interesen:\n{$list}\nPara añadir uno: *agrega 1 nombre_producto*";
                            // Marcar fingerprint como mostrado
                            $sessionShown[] = $fingerprint;
                            session()->put('rec_shown_fingerprints', $sessionShown);
                        }
                    }
                }
            }
        }

        return $base;
    }

    /**
     * Parsea frases de agregado simples cuando la NLU no detectó items.
     * Devuelve un arreglo de items con claves name y qty.
     */
    private function parseInlineAddExpression(string $text): array
    {
    $verbs = '(agrega|añade|suma|pon|poner|agregar|añadir|sumar|quiero|quisiera|deseo|necesito|me\s+gustaria|me\s+gustaría|podria|podría|ponme|traeme|tráeme|deme|dame)';
        $numsWords = '(un|una|uno|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez|docena|media\s+docena)';
        $re = '/^.*?' . $verbs . '\s+(?:' . $numsWords . '|(\d+))?\s*([a-záéíóúñ0-9][a-z0-9áéíóúñ\s-]{1,80})$/iu';

        if (!preg_match($re, $text, $m)) {
            return [];
        }

        // Determinar cantidad
        $wordQty = $m[3] ?? null; // según grupos del regex tras verb + (qty)
        $digitQty = isset($m[4]) ? $m[4] : null; // grupo de dígitos opcional
        // Ajustar: debido al patrón, reindexar cuidadosamente
        // Estructura esperada: [0]=full [1]=... antes? no; validamos con dump mental
        // Simplificamos: volvamos a hacer otro regex más explícito si hay dudas.
        $qty = 1;

        // Mejor segundo intento más simple para separar cantidad por palabras
        $simple = '/^(?:' . $verbs . ')\s+((?:' . $numsWords . '|\d+)?)\s*(.+)$/iu';
        if (preg_match($simple, $text, $mm)) {
            $rawQty = trim($mm[2] ?? '');
            $productPart = trim($mm[3] ?? '');
        } else {
            // fallback al primer match
            $productPart = trim($m[count($m)-1] ?? '');
            $rawQty = '';
        }

        $map = [
            'un'=>1,'una'=>1,'uno'=>1,
            'dos'=>2,'tres'=>3,'cuatro'=>4,'cinco'=>5,'seis'=>6,'siete'=>7,'ocho'=>8,'nueve'=>9,'diez'=>10,
            'media docena'=>6,'docena'=>12
        ];
        $rn = mb_strtolower($rawQty);
        if ($rn !== '') {
            if (isset($map[$rn])) {
                $qty = $map[$rn];
            } elseif (ctype_digit($rn)) {
                $qty = max(1, (int)$rn);
            }
        }

        // Limpiar producto: quitar artículos iniciales y palabras genéricas
    $productPart = preg_replace('/^(pedir|ordenar|comprar|solicitar)\s+/iu','', $productPart);
    $product = preg_replace('/^(de\s+|la\s+|el\s+|los\s+|las\s+)/u','', $productPart);
    $product = $this->stripPoliteSuffixes($product);
        $product = trim($product);
        if ($product === '') return [];

        return [[ 'name'=>$product, 'qty'=>$qty ]];
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
        $uid = auth()->id();
        if (!$uid && request()->bearerToken()) {
            $pat = PersonalAccessToken::findToken(request()->bearerToken());
            if ($pat) $uid = (int) $pat->tokenable_id;
        }

        // Si no está autenticado
        if (!$uid) {
            $global = $this->globalTopProducts(5);
            if (empty($global)) return 'Aún no tengo suficientes ventas para recomendar. ¿Quieres ver el catálogo?';
            $lines = collect($global)->map(fn($r)=>"• {$r['name']}".(isset($r['units'])?" ({$r['units']} uds)":""))->implode("\n");
            return "Algunos de nuestros más populares:\n{$lines}\n\n¿Te gustaría agregar alguno?";
        }

        $personal = $this->personalTopProducts($uid, 8);
        $now = now();

        // Filtramos para generar lista de candidatos ordenada por score
        if (!empty($personal)) {
            $cart = $this->getActiveCart(request());
            $cartIds = $cart ? CartItem::where('cart_id',$cart->id)->pluck('product_id')->map(fn($v)=>(int)$v)->all() : [];
            $inStockIds = $this->inStockProductIdMap(collect($personal)->pluck('product_id')->all());
            $filteredPersonal = collect($personal)
                ->reject(fn($p)=> in_array($p['product_id'],$cartIds,true))
                ->filter(fn($p)=> isset($inStockIds[$p['product_id']]))
                ->values();
            if ($filteredPersonal->isNotEmpty()) {
                $lines = $filteredPersonal->take(5)->map(function($p) use ($now){
                    $last   = $p['last_date'] ? \Carbon\Carbon::parse($p['last_date']) : null;
                    $days   = $last ? $last->diffInDays($now) : null;
                    $recencyTxt = $days === null ? '' : ($days === 0 ? 'hoy' : ($days === 1 ? 'ayer' : "hace {$days} días"));
                    $reasonParts = [];
                    if ($p['times']>1) $reasonParts[] = $p['times']." veces";
                    if ($recencyTxt) $reasonParts[] = $recencyTxt;
                    $reason = $reasonParts ? ' — '.implode(' · ', $reasonParts) : '';
                    return '• '.$p['name'].$reason;
                })->implode("\n");
                if ($lines) {
                    return "Basado en tus compras recientes te podría gustar:\n{$lines}\n\nPuedes agregar uno diciendo, por ejemplo: *agrega 2 cachitos*. ¿Quieres otra recomendación más específica?";
                }
            }
        }

        // Fallback si no hay historial o es muy corto: complementos a partir de popularidad global
        $global = $this->globalTopProducts(5);
        if (empty($global)) return 'Aún no tengo suficientes datos para recomendar. ¿Quieres decirme qué te gusta?';
        $lines = collect($global)->map(fn($r)=>"• {$r['name']}")->implode("\n");
        return "Te recomiendo probar:\n{$lines}\n\n¿Te gustaría añadir alguno?";
    }

    /**
     * Productos más comprados por el usuario (frecuencia + recencia)
     * Devuelve arreglo de: product_id, name, units, times, last_date, score
     */
    private function personalTopProducts(int $userId, int $limit = 10): array
    {
        // Agregamos ventas últimas 90 días (ajustable)
        $since = now()->subDays(90);
        $rows = \DB::table('order_items as oi')
            ->join('orders as o','o.id','=','oi.order_id')
            ->join('products as p','p.id','=','oi.product_id')
            ->where('o.user_id', $userId)
            ->where('o.created_at','>=',$since)
            ->selectRaw('oi.product_id, p.name, SUM(oi.quantity) as units, COUNT(*) as times, MAX(o.created_at) as last_date')
            ->groupBy('oi.product_id','p.name')
            ->get();

        if ($rows->isEmpty()) return [];

        $now = now();
        $scored = $rows->map(function($r) use ($now){
            $last = $r->last_date ? \Carbon\Carbon::parse($r->last_date) : $now->copy()->subDays(365);
            $days = max(1, $last->diffInDays($now));
            // Score simple: (units * 1.2 + times) * (1 / log(days+1)+0.5)
            $score = (($r->units * 1.2) + $r->times) * (1 / (log($days+1)+0.5));
            return [
                'product_id' => (int)$r->product_id,
                'name'       => $r->name,
                'units'      => (int)$r->units,
                'times'      => (int)$r->times,
                'last_date'  => $r->last_date,
                'score'      => $score,
            ];
        })->sortByDesc('score')->values();

        return $scored->take($limit)->all();
    }

    /**
     * Top global de productos (últimos 30 días) como fallback o para enriquecer.
     */
    private function globalTopProducts(int $limit = 10): array
    {
        $since = now()->subDays(30);
        $rows = \DB::table('order_items as oi')
            ->join('orders as o','o.id','=','oi.order_id')
            ->join('products as p','p.id','=','oi.product_id')
            ->where('o.created_at','>=',$since)
            ->whereIn('o.status',[ 'paid','completed','processing','cash_on_delivery' ])
            // 'lines' es palabra reservada (LOAD DATA ... LINES) en MySQL, usar alias distinto
            ->selectRaw('oi.product_id, p.name, SUM(oi.quantity) as units, COUNT(*) as sale_lines')
            ->groupBy('oi.product_id','p.name')
            ->orderByDesc('units')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            // fallback: simplemente productos con stock si no hay ventas
            $fallback = \DB::table('products')->where('stock','>',0)->select('id as product_id','name')->orderByDesc('updated_at')->limit($limit)->get();
            return $fallback->map(fn($f)=>['product_id'=>$f->product_id,'name'=>$f->name])->all();
        }
        return $rows->map(fn($r)=>[
            'product_id'=>(int)$r->product_id,
            'name'=>$r->name,
            'units'=>(int)$r->units,
        ])->all();
    }

    /**
     * Mapa de productos con stock > 0 para filtrar recomendaciones.
     */
    private function inStockProductIdMap(array $productIds): array
    {
        if (empty($productIds)) return [];
        $ids = \DB::table('products')
            ->whereIn('id', $productIds)
            ->where('stock','>',0)
            ->pluck('id');
        $map = [];
        foreach ($ids as $id) { $map[(int)$id] = true; }
        return $map;
    }

    /**
     * Recomendaciones por co-ocurrencia: productos que aparecen frecuentemente
     * en las mismas órdenes que cualquiera de los productos del carrito.
     */
    private function coOccurrenceRecommendations(array $cartProductIds, int $limit = 6): array
    {
        if (empty($cartProductIds)) return [];
        $since = now()->subDays(60);

        // Órdenes recientes que contienen al menos uno de los productos del carrito
        $orderIds = \DB::table('order_items as oi')
            ->join('orders as o','o.id','=','oi.order_id')
            ->whereIn('oi.product_id', $cartProductIds)
            ->where('o.created_at','>=',$since)
            ->whereIn('o.status',[ 'paid','completed','processing','cash_on_delivery' ])
            ->pluck('o.id')
            ->unique()
            ->values();
        if ($orderIds->isEmpty()) return [];

        $rows = \DB::table('order_items as oi')
            ->join('products as p','p.id','=','oi.product_id')
            ->whereIn('oi.order_id', $orderIds)
            ->whereNotIn('oi.product_id', $cartProductIds)
            // evitar alias reservado 'lines'
            ->select('oi.product_id','p.name', \DB::raw('SUM(oi.quantity) as units'), \DB::raw('COUNT(*) as cooc_count'), \DB::raw('MAX(oi.created_at) as last_date'))
            ->groupBy('oi.product_id','p.name')
            ->orderByDesc('units')
            ->limit($limit * 2) // tomar extra antes de filtrar stock
            ->get();
        if ($rows->isEmpty()) return [];

        $inStock = $this->inStockProductIdMap($rows->pluck('product_id')->map(fn($v)=>(int)$v)->all());
        $filtered = $rows->filter(fn($r)=> isset($inStock[(int)$r->product_id]));
        return $filtered->take($limit)->map(fn($r)=>[
            'product_id'=>(int)$r->product_id,
            'name'=>$r->name,
            'times'=>(int)$r->cooc_count,
            'last_date'=>$r->last_date,
        ])->all();
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

    /**
     * Elimina cortesías y fragmentos añadidos al final que no forman parte del nombre real del producto.
     * Ej: "croissant por favor", "pan campesino gracias", "golfeados porfa".
     */
    private function stripPoliteSuffixes(string $text): string
    {
        $t = trim($text);
        if ($t === '') return $t;
        // Normalizar acentos para comparar algunas variantes
        $lower = mb_strtolower($t);
        $lower = strtr($lower, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);

        // Expresiones de cortesía comunes (solo si van al final)
        $patterns = [
            '/\bpor\s+favor\b$/u',
            '/\bporfavor\b$/u',
            '/\bporfa\b$/u',
            '/\bporfis\b$/u',
            '/\bgracias\b$/u',
            '/\bpor\s+fa\b$/u',
            '/\bme\s+das\b$/u',
            '/\bpor\s+favorcito\b$/u'
        ];
        foreach ($patterns as $re) {
            $lower = preg_replace($re,'', $lower);
        }
        // quitar conectores residuales al final
        $lower = preg_replace('/[.,;:!¡¿?]+$/u','', $lower);
        $lower = trim($lower);

        // Si quedó un fragmento cortado como "croissant vor" (residuo de 'por favor') lo limpiamos
        // Detectar secuencias finales ' por', ' favor', ' vor'
        $courtesyTailTokens = ['por','favor','vor'];
        $parts = preg_split('/\s+/u', $lower);
        while (!empty($parts)) {
            $last = end($parts);
            if (in_array($last, $courtesyTailTokens, true)) {
                array_pop($parts);
                continue;
            }
            break;
        }
        $lower = trim(implode(' ', $parts));
        return $lower === '' ? $t : $lower;
    }

    /**
     * Parser adicional libre para frases tipo: "quisiera pedir dos croissant porfavor"
     * Devuelve array de items (name, qty) si detecta uno.
     */
    private function extractItemsFromFreeForm(string $text): array
    {
        $orig = mb_strtolower(trim($text));
        if ($orig === '') return [];
        $clean = $this->stripPoliteSuffixes($orig);
        // eliminar verbos de intención al inicio
        $clean = preg_replace('/^(quiero|quisiera|deseo|necesito|me\s+gustaria|me\s+gustaría|podria|podría|voy\s+a|vengo\s+a|para\s+pedir)\s+/u','', $clean);
        $clean = preg_replace('/^(pedir|ordenar|comprar|solicitar)\s+/u','', $clean);
        $clean = trim($clean);

        $map = [
            'un'=>1,'una'=>1,'uno'=>1,
            'dos'=>2,'tres'=>3,'cuatro'=>4,'cinco'=>5,'seis'=>6,'siete'=>7,'ocho'=>8,'nueve'=>9,'diez'=>10,
            'media docena'=>6,'docena'=>12
        ];

        // Detectar cantidad palabra compuesta "media docena" primero
        if (preg_match('/^(media\s+docena)\s+(.+)$/u',$clean,$m)) {
            $qty = 6; $name = trim($m[2]);
            $name = $this->stripPoliteSuffixes($name);
            if ($name!=='') return [[ 'name'=>$name, 'qty'=>$qty ]];
        }

        // General: (qtyWord|number)? product+  (al menos 1 token de producto)
        if (preg_match('/^(un|una|uno|dos|tres|cuatro|cinco|seis|siete|ocho|nueve|diez|\d+)\s+(.+)$/u',$clean,$m)) {
            $rawQty = $m[1];
            $name = trim($m[2]);
            $qty = isset($map[$rawQty]) ? $map[$rawQty] : (ctype_digit($rawQty)? max(1,(int)$rawQty):1);
            $name = $this->stripPoliteSuffixes($name);
            // quitar artículos iniciales redundantes
            $name = preg_replace('/^(de\s+|la\s+|el\s+|los\s+|las\s+)/u','', $name);
            if ($name!=='') return [[ 'name'=>$name, 'qty'=>$qty ]];
        }

        // fallback: si empieza con un producto sin cantidad explícita asumimos 1
        // Tomar primeras 3 palabras como posible producto si contiene letras
        if (preg_match('/^([a-záéíóúñ0-9][a-z0-9áéíóúñ\s-]{2,80})$/u',$clean,$m)) {
            $name = trim($m[1]);
            $name = $this->stripPoliteSuffixes($name);
            if ($name!=='') return [[ 'name'=>$name, 'qty'=>1 ]];
        }
        return [];
    }

    /**
     * Búsqueda aproximada si el matching directo no encontró producto.
     */
    private function findApproxProduct(string $raw): ?Product
    {
        $norm = preg_replace('/\s+/u',' ', trim($raw));
        if ($norm==='') return null;
        // tokens significativos (>=2 chars)
        $tokens = array_filter(preg_split('/\s+/u',$norm), fn($t)=>mb_strlen($t)>=2);
        if (!$tokens) return null;
        $q = Product::query();
        foreach ($tokens as $tk) {
            $q->orWhere('name','like','%'.$tk.'%');
        }
        $candidates = $q->limit(25)->get();
        if ($candidates->isEmpty()) return null;
        $best = null; $bestDist = 999;
        $target = $this->normalizeProdFragment($norm);
        foreach ($candidates as $cand) {
            $candNorm = $this->normalizeProdFragment($cand->name);
            $dist = levenshtein($target, $candNorm);
            $len = max(mb_strlen($target), mb_strlen($candNorm));
            $threshold = $len <= 8 ? 2 : ($len <= 14 ? 3 : 4);
            if ($dist <= $threshold && $dist < $bestDist) { $best = $cand; $bestDist = $dist; }
        }
        return $best;
    }





}
