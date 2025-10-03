<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DecisionAdvisorService
{
    public function __construct(private AnalyticsService $analytics) {}

    // A) Estadísticas diarias por producto (para demanda, stock y producción)

public function dailyStatsPerProduct(int $days = 14): array {
    $from = Carbon::now()->subDays($days - 1)->startOfDay()->toDateTimeString();
    $to   = Carbon::now()->endOfDay()->toDateTimeString();

    $rows = DB::table('order_items as oi')
        ->join('orders as o','o.id','=','oi.order_id')
        ->whereIn('o.status',['paid','completed'])
        ->whereBetween('o.created_at', [$from,$to])
        ->select(
            'oi.product_id',
            DB::raw('DATE(o.created_at) as d'),
            DB::raw('SUM(oi.quantity) as units')
        )
        ->groupBy('oi.product_id', DB::raw('DATE(o.created_at)'))
        ->get()
        ->groupBy('product_id');

    // calendario de los últimos N días (incluye ceros)
    $calendar = [];
    for ($i = 0; $i < $days; $i++) {
        $calendar[] = Carbon::now()->subDays($i)->toDateString();
    }

    $out = [];
    foreach ($rows as $pid => $series) {
        $byDate = $series->keyBy('d');
        $vals = [];
        foreach ($calendar as $d) {
            $vals[] = (float) ($byDate[$d]->units ?? 0.0);  // 👈 días sin ventas = 0
        }

        $n   = max(1, count($vals));
        $avg = array_sum($vals) / $n;
        $sd  = $this->stddev($vals);

        // Suavizados para evitar locuras con poco histórico
        $avg = max(0.0, round($avg, 3));
        $sd  = max(0.0, round($sd, 3));

        $out[$pid] = ['avg'=>$avg, 'sd'=>$sd, 'days'=>$n];
    }

    // Productos que no aparecieron en rows (cero ventas en N días) → registrar con ceros
    $allPids = DB::table('products')->pluck('id');
    foreach ($allPids as $pid) {
        if (!isset($out[$pid])) {
            $out[$pid] = ['avg'=>0.0, 'sd'=>0.0, 'days'=>$days];
        }
    }

    return $out;
}


public function stockRecommendation(
    int $leadTimeDays = 2,
    float $serviceZ = 1.65,
    int $reviewPeriodDays = 1,
    float $minDailyAvg = 0.2,
    bool $onlyNeeds = true,     // 👈 nuevo
    int $maxItems = 12,         // 👈 nuevo
    int $minGapUnits = 1   // ignora productos con <0.2 uds/día
): array {
    $stats = $this->dailyStatsPerProduct(14);
    $inv = DB::table('products')->select('id','name','stock')->get()->keyBy('id');

    $protect = max(1, $leadTimeDays + $reviewPeriodDays); // periodo de protección

    $out = [];
    foreach ($stats as $pid => $st) {
        $avg = (float) $st['avg'];
        $sd  = (float) $st['sd'];
        $onH = (float) ($inv[$pid]->stock ?? 0);

        // Evitar “recomendaciones” cuando casi no hay consumo
        if ($avg < $minDailyAvg && $onH > 0) {
            continue;
        }

        $ss   = $serviceZ * $sd * sqrt($protect);
        $tgt  = $avg * $protect + $ss;                 // nivel objetivo
        $gap  = $tgt - $onH;
        $buy  = max(0, (int) ceil($gap));
        

        if ($onlyNeeds && $buy < $minGapUnits) {
            continue;
        }

        // Reorder point informativo (consumo durante lead time + SS parcial)
        $rop  = $avg * $leadTimeDays + ($serviceZ * $sd * sqrt($leadTimeDays));

        $out[] = [
            'product_id'        => $pid,
            'name'              => $inv[$pid]->name ?? ("#".$pid),
            'on_hand'           => $onH,
            'daily_avg'         => round($avg, 2),
            'daily_sd'          => round($sd, 2),
            'protection_days'   => $protect,
            'safety_stock'      => (int) ceil($ss),
            'reorder_point'     => (int) ceil($rop),
            'target_level'      => (int) ceil($tgt),
            'suggested_purchase'=> $buy,
        ];
    }

    // Ordena por necesidad real de compra
    usort($out, fn($a,$b)=> $b['suggested_purchase'] <=> $a['suggested_purchase']);
    return array_slice($out, 0, $maxItems);

}


    // C) Plan de producción “mañana” (promedio del mismo día de semana)
    public function productionPlanForTomorrow(int $weeksBack = 4, int $topN = 8): array {
        $tomorrowDow = Carbon::now()->addDay()->dayOfWeekIso; // 1=Lunes..7=Domingo
        $from = Carbon::now()->subWeeks($weeksBack)->startOfDay()->toDateTimeString();
        $to   = Carbon::now()->endOfDay()->toDateTimeString();

        $rows = DB::table('order_items as oi')
            ->join('orders as o','o.id','=','oi.order_id')
            ->join('products as p','p.id','=','oi.product_id')
            ->whereIn('o.status',['paid','completed'])
            ->whereBetween('o.created_at', [$from,$to])
            ->whereRaw('EXTRACT(ISODOW FROM o.created_at) = ?', [$tomorrowDow])
            ->select('oi.product_id','p.name', DB::raw('AVG(oi.quantity) as avg_qty'))
            ->groupBy('oi.product_id','p.name')
            ->orderByDesc(DB::raw('AVG(oi.quantity)'))
            ->limit($topN)
            ->get();

        // factor horario básico desde peakHours (para saber refuerzo de mañana 6–10am)
        $ph = $this->analytics->peakHours(
            Carbon::now()->subDays(30)->startOfDay()->toDateTimeString(),
            Carbon::now()->endOfDay()->toDateTimeString()
        );

        $morningWeight = collect($ph)->whereIn('hour',[6,7,8,9,10])->sum('orders');
        $dayWeight     = collect($ph)->sum('orders');
        $morningShare  = $dayWeight>0 ? $morningWeight/$dayWeight : 0.4; // default 40%

        return $rows->map(fn($r)=>[
            'product_id'=>$r->product_id,
            'name'=>$r->name,
            'tomorrow_target'=> max(1, (int)ceil($r->avg_qty * (1+$morningShare))), // empujar algo más a la mañana
            'note'=> $morningShare>=0.4 ? 'reforzar hornadas 6–10am' : 'distribuir parejo',
        ])->all();
    }

    // D) Combos recomendados (desde market basket)
    public function comboIdeas(float $minSupport=0.02, float $minConf=0.2, int $limit=10): array {
        $from = Carbon::now()->subDays(30)->startOfDay()->toDateTimeString();
        $to   = Carbon::now()->endOfDay()->toDateTimeString();
        $rules = $this->analytics->marketBasket($from,$to,$minSupport,$minConf,50);
        $rules = array_slice($rules,0,$limit);
        // mensaje de acción
        return array_map(fn($r)=>[
            'text'=> "Si compran *{$r['antecedent']}*, ofrece *{$r['consequent']}* (conf. ".round($r['confidence']*100)."%)",
            'antecedent'=>$r['antecedent'],
            'consequent'=>$r['consequent'],
        ], $rules);
    }

    // E) Acciones por RFM (quién reactivar / fidelizar)
    public function rfmActions(): array {
        $snap = $this->analytics->rfm(Carbon::now()->endOfDay()->toDateTimeString());
        $grp  = collect($snap)->groupBy('segment');
        return [
            'reactivar' => ($grp['111'] ?? collect())->count(), // inactivos
            'fidelizar' => ($grp['333'] ?? collect())->count(), // mejores
            'atencion'  => ($grp['132'] ?? collect())->count() + ($grp['123'] ?? collect())->count(), // gasto alto pero poca frecuencia o recencia
        ];
    }

    private function stddev(array $values): float {
        $n = count($values);
        if ($n<=1) return 0.0;
        $m = array_sum($values)/$n;
        $s = 0.0; foreach ($values as $v) $s += ($v-$m)*($v-$m);
        return sqrt($s/($n-1));
    }
}
