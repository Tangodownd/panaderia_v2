<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    public function topProducts(string $from, string $to, int $limit = 10)
    {
        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->whereIn('o.status', ['paid','completed'])
 // ajusta a tu estado “finalizado”
            ->select(
                'oi.product_id',
                'p.name',
                DB::raw('SUM(oi.quantity) as units'),
                DB::raw('SUM(oi.quantity * oi.price) as revenue')
            )
            ->groupBy('oi.product_id', 'p.name')
            ->orderByDesc('units')
            ->limit($limit)
            ->get();
    }

    public function peakHours(string $from, string $to)
    {
        return DB::table('orders as o')
            ->whereBetween('o.created_at', [$from, $to])
            ->whereIn('o.status', ['paid','completed'])
            ->select(
                DB::raw('EXTRACT(HOUR FROM o.created_at) as hour'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy(DB::raw('EXTRACT(HOUR FROM o.created_at)'))
            ->orderBy('hour')
            ->get();
    }

    // RFM por cliente (Recency días, Frequency #ordenes, Monetary total)
    public function rfm(string $to, ?int $maxCustomers = 5000)
    {
        $toDate = Carbon::parse($to);
        $rows = DB::table('orders as o')
            ->whereIn('o.status', ['paid','completed'])
            ->select(
                'o.customer_id',
                DB::raw('MAX(o.created_at) as last_order_at'),
                DB::raw('COUNT(*) as frequency'),
                DB::raw('SUM(o.total) as monetary')
            )
            ->groupBy('o.customer_id')
            ->when($maxCustomers, fn($q)=>$q->limit($maxCustomers))
            ->get();

        // Post-proceso: calcular recency (días) y clasificar R/F/M en terciles
        $recencies = [];
        foreach ($rows as $r) {
            $recencies[] = $toDate->diffInDays(Carbon::parse($r->last_order_at));
        }
        sort($recencies);
        $rfm = [];
        $rCuts = $this->cuts($recencies); // terciles
        $freqs = $rows->pluck('frequency')->sort()->values()->all();
        $fCuts = $this->cuts($freqs);
        $mons  = $rows->pluck('monetary')->sort()->values()->all();
        $mCuts = $this->cuts($mons);

        foreach ($rows as $r) {
            $rec = $toDate->diffInDays(Carbon::parse($r->last_order_at));
            $rScore = $this->scoreInverse($rec, $rCuts); // menor recency => mejor (3)
            $fScore = $this->scoreDirect($r->frequency, $fCuts);
            $mScore = $this->scoreDirect($r->monetary, $mCuts);
            $segment = "{$rScore}{$fScore}{$mScore}";
            $rfm[] = [
                'customer_id' => $r->customer_id,
                'recency_days' => $rec,
                'frequency' => (int)$r->frequency,
                'monetary' => (float)$r->monetary,
                'r' => $rScore, 'f' => $fScore, 'm' => $mScore,
                'segment' => $segment, // 333: mejores; 111: peores
            ];
        }
        return $rfm;
    }

    // Frequent 2-itemsets (co-ocurrencias) y confidence/lift simple
    public function marketBasket(string $from, string $to, float $minSupport = 0.02, float $minConfidence = 0.1, int $limit = 50)
    {
        // Total órdenes en rango
        $totalOrders = DB::table('orders as o')
            ->whereBetween('o.created_at', [$from, $to])
            ->whereIn('o.status', ['paid','completed'])
            ->count();

        if ($totalOrders === 0) return [];

        // Pares de productos por misma orden (2-itemsets)
        $pairs = DB::table('order_items as a')
            ->join('order_items as b', function ($j) {
                $j->on('a.order_id', '=', 'b.order_id')->whereRaw('a.product_id < b.product_id');
            })
            ->join('orders as o', 'o.id', '=', 'a.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->whereIn('o.status', ['paid','completed'])
            ->select('a.product_id as p1', 'b.product_id as p2', DB::raw('COUNT(DISTINCT a.order_id) as orders'))
            ->groupBy('a.product_id', 'b.product_id')
            ->havingRaw('COUNT(DISTINCT a.order_id) >= 1')
            ->get();

        if ($pairs->isEmpty()) return [];

        // Soportes individuales
        $support1 = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->whereIn('o.status', ['paid','completed'])
            ->select('oi.product_id', DB::raw('COUNT(DISTINCT oi.order_id) as orders'))
            ->groupBy('oi.product_id')
            ->pluck('orders','product_id');

        // nombres
        $names = DB::table('products')->pluck('name','id');

        $rules = [];
        foreach ($pairs as $row) {
            $supportPair = $row->orders / $totalOrders; // soporte del par
            if ($supportPair < $minSupport) continue;

            $aOrders = $support1[$row->p1] ?? 0;
            $bOrders = $support1[$row->p2] ?? 0;
            if ($aOrders == 0 || $bOrders == 0) continue;

            // Confidences A→B y B→A
            $confAB = $row->orders / $aOrders;
            $confBA = $row->orders / $bOrders;

            if ($confAB >= $minConfidence) {
                $rules[] = [
                    'antecedent_id' => $row->p1,
                    'consequent_id' => $row->p2,
                    'antecedent' => $names[$row->p1] ?? $row->p1,
                    'consequent' => $names[$row->p2] ?? $row->p2,
                    'support' => round($supportPair, 4),
                    'confidence' => round($confAB, 4),
                ];
            }
            if ($confBA >= $minConfidence) {
                $rules[] = [
                    'antecedent_id' => $row->p2,
                    'consequent_id' => $row->p1,
                    'antecedent' => $names[$row->p2] ?? $row->p2,
                    'consequent' => $names[$row->p1] ?? $row->p1,
                    'support' => round($supportPair, 4),
                    'confidence' => round($confBA, 4),
                ];
            }
        }

        // top-N por confidence
        usort($rules, fn($x,$y)=>$y['confidence']<=>$x['confidence']);
        return array_slice($rules, 0, $limit);
    }

    // Ventas diarias + z-score para anomalías (picos/caídas)
    public function dailyAnomalies(string $from, string $to, float $z = 2.0)
    {
        $rows = DB::table('orders as o')
            ->whereBetween('o.created_at', [$from, $to])
            ->whereIn('o.status', ['paid','completed'])
            ->select(
                DB::raw('DATE(o.created_at) as d'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(o.total) as revenue')
            )
            ->groupBy(DB::raw('DATE(o.created_at)'))
            ->orderBy('d')
            ->get();

        if ($rows->isEmpty()) return [];

        $values = $rows->pluck('revenue')->values()->all();
        $mean = array_sum($values)/count($values);
        $sd = $this->stddev($values);
        $out = [];
        foreach ($rows as $r) {
            $zv = $sd>0 ? (($r->revenue - $mean)/$sd) : 0;
            if (abs($zv) >= $z) {
                $out[] = [
                    'date' => $r->d,
                    'revenue' => (float)$r->revenue,
                    'z' => round($zv,2),
                    'type' => $zv>0 ? 'spike' : 'drop',
                ];
            }
        }
        return ['series'=>$rows, 'anomalies'=>$out, 'mean'=>$mean, 'sd'=>$sd];
    }

    // --- helpers ---
    private function cuts(array $sortedValues) {
        $n = count($sortedValues);
        if ($n < 3) return [INF, INF]; // evita div/0
        $t1 = $sortedValues[(int) floor($n/3)];
        $t2 = $sortedValues[(int) floor(2*$n/3)];
        return [$t1, $t2];
    }
    private function scoreDirect($v, $cuts) {
        if ($v <= $cuts[0]) return 1;
        if ($v <= $cuts[1]) return 2;
        return 3;
    }
    private function scoreInverse($v, $cuts) {
        // menor = mejor
        if ($v <= $cuts[0]) return 3;
        if ($v <= $cuts[1]) return 2;
        return 1;
    }
    private function stddev($values) {
        $n = count($values);
        if ($n <= 1) return 0;
        $mean = array_sum($values)/$n;
        $sum = 0;
        foreach ($values as $v) $sum += ($v - $mean)**2;
        return sqrt($sum/($n-1));
    }
}
