<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiClient
{
  private string $base;
  private int $timeout;

  public function __construct()
  {
    $this->base = rtrim(env('AI_SERVICE_BASE_URL','http://127.0.0.1:8001'),'/');
    $this->timeout = (int) env('AI_SERVICE_TIMEOUT', 3);
  }

  public function interpret(string $text, array $context=[]): array
  {
    return $this->post('/interpret', ['text'=>$text, 'context'=>$context]);
  }

  public function normalize(string $text): array
  {
    return $this->post('/normalize', ['text'=>$text]);
  }

  public function recommend(array $cart, ?int $customerId=null): array
  {
    return $this->post('/recommend', ['cart'=>$cart, 'customer_id'=>$customerId]);
  }

  private function post(string $path, array $payload): array
  {
    $res = Http::timeout($this->timeout)->acceptJson()->post($this->base.$path, $payload);
    if (!$res->ok()) return ['intent'=>'FALLBACK','error'=>$res->body()];
    return $res->json();
  }
}
