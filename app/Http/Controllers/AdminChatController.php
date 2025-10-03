<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AiClient;
use App\Services\AnalyticsService;
use App\Services\DecisionAdvisorService;
use App\Support\DateRanges;

class AdminChatController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics,
        private DecisionAdvisorService $advisor
    ) {}

    private function ensureAdminScope(): void
    {
        $u = request()->user();
        // Defensa extra además del middleware. Acepta:
        //  - Método isAdmin() basado en 'role' (implementado en User.php)
        //  - Columna legacy 'is_admin' booleana (si existiera)
        $isAdmin = false;
        if ($u) {
            if (method_exists($u, 'isAdmin')) {
                $isAdmin = $u->isAdmin();
            }
            // fallback por compatibilidad
            if (!$isAdmin && isset($u->is_admin)) {
                $isAdmin = (bool) $u->is_admin;
            }
        }

        if (!$u || !$isAdmin) {
            // No usamos abort(response()) porque abort espera un código o excepción.
            // Simplemente devolvemos respuesta y lanzamos HttpException para cortar.
            response()->json(['error' => 'Solo disponible en el chat administrativo.'], 403)->throwResponse();
        }
    }

    public function process(Request $req, AiClient $ai)
    {
        $this->ensureAdminScope();

        $text = trim((string) $req->input('text',''));

        $nlu = $ai->interpret($text, [
            'session_id' => session()->getId(),
            'user_id'    => $req->user()?->id,
            'profile'    => 'admin', // (opcional) ayuda a tu NLU
        ]);

        $intent   = $nlu['intent']   ?? 'SMALL_TALK';
        $entities = $nlu['entities'] ?? [];

        // Lista blanca admin
        $allowed = ['ANALYTICS_REPORT','DECISION_SUPPORT','SMALL_TALK'];
        if (!in_array($intent, $allowed, true)) {
            // Heurística: si suena a decisiones, rutea a DECISION_SUPPORT; si no, a ANALYTICS_REPORT
            $t = mb_strtolower($text);
            $intent = (
                str_contains($t,'stock') || str_contains($t,'reorden')
                || (str_contains($t,'recomend') && str_contains($t,'compr'))
                || str_contains($t,'plan de produccion') || str_contains($t,'plan de producción')
            ) ? 'DECISION_SUPPORT' : 'ANALYTICS_REPORT';
        }

        $reply = match ($intent) {
            'ANALYTICS_REPORT' => $this->handleAnalyticsReport($nlu),
            'DECISION_SUPPORT' => $this->handleDecisionSupport($nlu),
            default => "¿Qué análisis necesitas? (top, horas pico, RFM, market basket, *stock recomendado*, *plan de producción*, combos, acciones RFM)",
        };

        return response()->json(['reply' => $reply]);
    }

    /** --------- ANALÍTICA --------- */
    private function handleAnalyticsReport(array $nlu): string
    {
        $this->ensureAdminScope();

        $text = (string) request()->input('text','');
        $t = mb_strtolower($text);

        [$from, $to] = DateRanges::parse($text);
        $fromS = $from->toDateTimeString();
        $toS   = $to->toDateTimeString();

        if (str_contains($t, 'tendencia') || str_contains($t,'trend')) {
            // detectar producto tras palabra 'de' o último token relevante
            $pattern = '/tendencia(?:\s+de)?\s+([\p{L}\p{N}\s_-]{2,40})/u';
            $productQ = null;
            if (preg_match($pattern, $t, $m)) {
                $productQ = trim($m[1]);
            } else {
                // fallback: buscar última palabra no genérica
                $tokens = array_filter(preg_split('/\s+/u',$t));
                $stop = ['cual','es','la','el','de','del','las','los','tendencia','trend'];
                $candidate = null;
                foreach (array_reverse($tokens) as $tok) {
                    if (in_array($tok,$stop,true)) continue; $candidate=$tok; break;
                }
                $productQ = $candidate;
            }
            if (!$productQ) return "📈 Indica el producto: *tendencia croissant*";
            $trend = $this->analytics->productTrend($fromS,$toS,$productQ);
            if (!$trend) return "📈 No encontré datos para '$productQ' en el rango.";
            $seriesTxt = collect($trend['series'])->take(7)->map(fn($r)=> $r['date'].': '.$r['units'])->join(', ');
            return "📈 *Tendencia* {$trend['product']} — avg7: {$trend['avg7']} uds, crecimiento: {$trend['growth_pct']}%\nÚltimos días: {$seriesTxt}";
        }

        if (str_contains($t, 'categoria') || str_contains($t,'categoría') || str_contains($t,'category share')) {
            $share = $this->analytics->categoryShare($fromS,$toS,8);
            if (!$share) return "📊 *Participación por categoría* — sin datos";
            $lines = collect($share)->map(fn($c)=>"- {$c['category']}: ".number_format($c['share'],2)."% (Bs. ".number_format($c['revenue'],2).")")->join("\n");
            return "📊 *Participación por categoría* — $fromS → $toS\n$lines";
        }

        if (str_contains($t,'abc') || str_contains($t,'clasificacion') || str_contains($t,'clasificación')) {
            $abc = $this->analytics->inventoryABC($fromS,$toS);
            if (!$abc) return "🔤 *ABC* — sin ventas";
            $lines = collect($abc)->take(12)->map(fn($r)=>"- {$r['class']} {$r['name']} ({$r['cum_share']}% acumulado)")->join("\n");
            return "🔤 *Clasificación ABC* — (A≈80%, B≈95%)\n$lines";
        }

        if (str_contains($t,'anomalia') || str_contains($t,'anomalía') || str_contains($t,'pico raro') || str_contains($t,'caida') || str_contains($t,'caída')) {
            $anom = $this->analytics->dailyAnomalies($fromS,$toS,2.2);
            if (!$anom || empty($anom['anomalies'])) return "⚠️ *Anomalías* — No se detectaron picos o caídas significativos.";
            $lines = collect($anom['anomalies'])->map(fn($a)=>"- {$a['date']}: {$a['type']} (z={$a['z']}) Bs. ".number_format($a['revenue'],2))->join("\n");
            return "⚠️ *Anomalías de ingresos*\n$lines";
        }

        if (str_contains($t, 'pico') || str_contains($t, 'hora')) {
            $data = $this->analytics->peakHours($fromS, $toS);
            if (!$data) return "⏰ *Horas pico* — $fromS → $toS\nNo hay datos en el rango.";
            $lines = collect($data)->map(fn($r)=> sprintf("- %02d:00 → %d pedidos (Bs. %.2f)", $r->hour, $r->orders, $r->revenue))->join("\n");
            return "⏰ *Horas pico* — $fromS → $toS\n".$lines;
        }

        if (str_contains($t, 'market') || str_contains($t, 'carrito')) {
            $rules = $this->analytics->marketBasket($fromS, $toS, 0.02, 0.1, 20);
            if (!$rules) return "🧺 *Market basket* — $fromS → $toS\nSin co-ocurrencias suficientes.";
            $lines = collect($rules)->map(fn($r)=> "- Si compran *{$r['antecedent']}*, recomienda *{$r['consequent']}* (conf. ".number_format($r['confidence']*100,1)."%, sup. ".number_format($r['support']*100,1)."%)")->join("\n");
            return "🧺 *Market basket* — $fromS → $toS\n".$lines;
        }

        if (str_contains($t, 'rfm')) {
            $rfm = $this->analytics->rfm($toS);
            if (!$rfm) return "👥 *RFM* — al $toS\nNo hay clientes con compras.";
            $segCounts = collect($rfm)->groupBy('segment')->map->count()->sortDesc()->take(8);
            $lines = $segCounts->map(fn($c,$seg)=>"- $seg → $c clientes")->join("\n");
            return "👥 *RFM* — al $toS\nSegmentos más frecuentes:\n{$lines}\n(333 = mejores; 111 = inactivos)";
        }

        // Top productos (default)
        $top = $this->analytics->topProducts($fromS, $toS, 10);
        if (!$top) return "📦 *Top productos* — $fromS → $toS\nSin ventas.";
        $lines = collect($top)->map(fn($r)=> "- {$r->name}: {$r->units} uds (Bs. ".number_format($r->revenue,2).")")->join("\n");
        return "📦 *Top productos* — $fromS → $toS\n".$lines;
    }

    /** --------- DECISIONES --------- */
    private function handleDecisionSupport(array $nlu): string
    {
        $this->ensureAdminScope();

        $t = mb_strtolower((string) request()->input('text',''));

        // a) Stock recomendado
        if (str_contains($t,'stock') || str_contains($t,'reorden') || (str_contains($t,'recomend') && str_contains($t,'compr'))) {
            $rows = $this->advisor->stockRecommendation(
                leadTimeDays: 2,     // LT
                serviceZ: 1.65,      // ~95% servicio
                reviewPeriodDays: 1, // revisión diaria
                minDailyAvg: 0.2,    // filtra productos con venta insignificante
                onlyNeeds: true,     // solo los que necesitan compra
                maxItems: 12,        // corta a los 12 más urgentes
                minGapUnits: 1       // evita “comprar 0”
            );
            if (empty($rows)) {
                return "📦 *Stock recomendado* — No hace falta comprar por ahora (inventario cubre la demanda proyectada).";
            }
            $lines = collect($rows)->map(fn($r)=>
                "- {$r['name']}: en mano {$r['on_hand']}, objetivo {$r['target_level']} (avg/día {$r['daily_avg']}) → comprar *{$r['suggested_purchase']}*"
            )->join("\n");
            return "📦 *Stock recomendado*\n(basado en 14 días de ventas, tiempo de reposición estimado en 2 días y revisión diaria)\n".$lines;
        }

        // b) Plan de producción (mañana)
        if (str_contains($t,'plan de produccion') || str_contains($t,'plan de producción') || str_contains($t,'produc')) {
            $plan = $this->advisor->productionPlanForTomorrow(weeksBack: 4, topN: 8);
            if (!$plan) return "👩‍🍳 *Plan de producción (mañana)* — No hay histórico suficiente.";
            $lines = collect($plan)->map(fn($r)=> "- {$r['name']}: objetivo {$r['tomorrow_target']} ({$r['note']})")->join("\n");
            return "👩‍🍳 *Plan de producción (mañana)* — basado en el mismo día de la semana\n".$lines;
        }

        // c) Combos recomendados
        if (str_contains($t,'combo')) {
            $combos = $this->advisor->comboIdeas(0.02, 0.2, 10);
            if (!$combos) return "🧺 *Combos recomendados* — Aún no detecto asociaciones fuertes.";
            $lines = collect($combos)->map(fn($c)=> "- ".$c['text'])->join("\n");
            return "🧺 *Combos recomendados* — últimos 30 días\n".$lines;
        }

        // d) Acciones por clientes (RFM)
        if (str_contains($t,'estrateg') || str_contains($t,'decisiones') || str_contains($t,'acciones')) {
            $a = $this->advisor->rfmActions();
            return "🎯 *Acciones sugeridas*\n- Reactivar: {$a['reactivar']} clientes (cupón/whatsapp)\n- Fidelizar: {$a['fidelizar']} (club/upsell)\n- Atención: {$a['atencion']} (recordatorios y combos)";
        }

        return "¿Qué necesitas? (*stock recomendado*, *plan de producción*, combos, acciones RFM, top, horas pico, market basket, RFM, tendencia producto, participación categoría, ABC, anomalías)";
    }
}
