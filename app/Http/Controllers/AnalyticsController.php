<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnalyticsService;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $svc) {}

    private function range(Request $r): array {
        // soporta “from/to” explícitos, por defecto últimos 30 días
        $from = $r->query('from') ?: Carbon::now()->subDays(30)->startOfDay()->toDateTimeString();
        $to   = $r->query('to')   ?: Carbon::now()->endOfDay()->toDateTimeString();
        return [$from, $to];
    }

    public function topProducts(Request $r) {
        [$from,$to] = $this->range($r);
        $limit = (int)($r->query('limit', 10));
        return response()->json(['from'=>$from,'to'=>$to,'data'=>$this->svc->topProducts($from,$to,$limit)]);
    }

    public function peakHours(Request $r) {
        [$from,$to] = $this->range($r);
        return response()->json(['from'=>$from,'to'=>$to,'data'=>$this->svc->peakHours($from,$to)]);
    }

    public function rfm(Request $r) {
        $to = $r->query('to') ?: Carbon::now()->endOfDay()->toDateTimeString();
        $max = (int)$r->query('limit', 5000);
        return response()->json(['to'=>$to,'data'=>$this->svc->rfm($to,$max)]);
    }

    public function marketBasket(Request $r) {
        [$from,$to] = $this->range($r);
        $minSupport = (float)$r->query('minSupport', 0.02);
        $minConf    = (float)$r->query('minConfidence', 0.1);
        $limit      = (int)$r->query('limit', 50);
        return response()->json([
            'from'=>$from,'to'=>$to,
            'data'=>$this->svc->marketBasket($from,$to,$minSupport,$minConf,$limit)
        ]);
    }

    public function dailyAnomalies(Request $r) {
        [$from,$to] = $this->range($r);
        $z = (float)$r->query('z', 2.0);
        return response()->json(['from'=>$from,'to'=>$to,'data'=>$this->svc->dailyAnomalies($from,$to,$z)]);
    }
}
