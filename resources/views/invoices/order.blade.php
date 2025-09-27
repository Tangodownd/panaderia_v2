<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <title>Factura #{{ str_pad($order->id ?? 0, 4, '0', STR_PAD_LEFT) }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <style>
    :root{ --brown:#8B4513; --cream:#FFF8E7; --beige:#F5E6D3; --ink:#222; }
    *{ box-sizing:border-box; }
    body{ font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu,Cantarell,Arial; color:var(--ink); margin:0; }

    .invoice{ max-width:850px; margin:24px auto; padding:24px; border:1px solid #eee; border-radius:10px; }
    .head{ display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .brand{ display:flex; align-items:center; gap:14px; }
    .logo{ width:54px;height:54px;border-radius:12px;background:var(--brown);display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:800; }
    h1{ margin:0; font-size:22px; letter-spacing:.3px; }
    .meta{ text-align:right; font-size:13px; color:#444; }
    .badge{ display:inline-block; padding:3px 8px; border-radius:999px; background:var(--beige); color:var(--brown); font-weight:600; font-size:12px; }

    .row{ display:flex; gap:24px; margin-top:18px; }
    .card{ flex:1; background:var(--cream); border:1px solid #f0e6d8; border-radius:10px; padding:14px 16px; }
    .card h3{ margin:0 0 8px; font-size:14px; color:#333; text-transform:uppercase; letter-spacing:.4px; }

    table{ width:100%; border-collapse:collapse; margin-top:16px; }
    th, td{ padding:10px 8px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th{ background:#faf7f0; font-size:12px; text-transform:uppercase; color:#555; letter-spacing:.4px; }
    .tright{ text-align:right; }

    .totals{ max-width:340px; margin-left:auto; margin-top:12px; }
    .totals .line{ display:flex; justify-content:space-between; padding:6px 0; }
    .totals .grand{ font-weight:800; border-top:2px solid #eee; padding-top:10px; font-size:16px; }

    .note{ margin-top:18px; font-size:12px; color:#666; }
    .footer{ margin-top:20px; font-size:12px; color:#777; display:flex; justify-content:space-between; }

    @media print{ .invoice{ border:none; margin:0; border-radius:0; } }
  </style>
</head>
<body>
@php
  // ---- Helpers de formato y totales ----
  $fmt = fn ($n) => number_format((float) $n, 2, '.', ',');
  $invoiceNumber = str_pad($order->id ?? 0, 4, '0', STR_PAD_LEFT);
  $dateStr = optional($order->created_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
  $status = $order->status ?? '—';

  $subtotal = 0.0;
  $rows = [];
  foreach ($items ?? [] as $it) {
      $name = $it->product->name ?? $it->name ?? ('Producto #'.($it->product_id ?? ''));
      $qty = (int) ($it->quantity ?? 1);
      $unit = (float) ($it->price ?? $it->unit_price ?? 0);
      $line = $qty * $unit;
      $subtotal += $line;
      $rows[] = compact('name','qty','unit','line');
  }

  // Si ya traes total/tax desde BD, respétalos. Si no, calcula (IVA 16%).
  $taxRate = isset($order->tax_rate) ? (float)$order->tax_rate : 0.16;
  $tax = isset($order->tax) ? (float)$order->tax : round($subtotal * $taxRate, 2);
  $total = isset($order->total) ? (float)$order->total : ($subtotal + $tax);

  $customerName  = $order->name ?? 'Cliente';
  $address       = $order->shipping_address ?? '—';
  $customerPhone = $order->phone ?? '—';
  $customerEmail = $order->email ?? null;

  // Opcional: atendido por (si existe)
  $attendedBy = $order->created_by_name ?? $order->created_by ?? 'Sistema';
@endphp

  <div class="invoice">
    <div class="head">
      <div class="brand">
        <div class="logo">PO</div>
        <div>
          <h1>Panadería Orquídea de Oro</h1>
          <div style="font-size:12px;color:#666">RIF: J-12345678-9 · +58 412-0000000 · Av. Principal, Valencia</div>
        </div>
      </div>
      <div class="meta">
        <div><strong>Factura #</strong> {{ $invoiceNumber }}</div>
        <div><strong>Fecha:</strong> {{ $dateStr }}</div>
        <div><span class="badge">Estado: {{ $status }}</span></div>
      </div>
    </div>

    <div class="row">
      <div class="card">
        <h3>Cliente</h3>
        <div><strong>{{ $customerName }}</strong></div>
        <div>{{ $address }}</div>
        <div>Tel: {{ $customerPhone }}</div>
        @if($customerEmail)
          <div>Email: {{ $customerEmail }}</div>
        @endif
      </div>
      <div class="card">
        <h3>Vendedor</h3>
        <div><strong>Panadería Orquídea de Oro</strong></div>
        <div>RIF: J-12345678-9</div>
        <div>Whatsapp: +58 412-0000000</div>
        <div>Dirección: Av. Principal, Valencia</div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th class="tright">Cant.</th>
          <th class="tright">Precio unit.</th>
          <th class="tright">Importe</th>
        </tr>
      </thead>
<tbody>
  @forelse($items as $x)
    <tr>
      <td>{{ $x['name'] }}</td>
      <td class="tright">{{ $x['qty'] }}</td>
      <td class="tright">{{ number_format((float)$x['price'], 2, '.', ',') }}</td>
      <td class="tright">{{ number_format((float)$x['total'], 2, '.', ',') }}</td>
    </tr>
  @empty
    <tr>
      <td colspan="4" class="tright" style="color:#777">Sin ítems</td>
    </tr>
  @endforelse
</tbody>
    </table>
<div class="totals">
  <div class="line">
    <span>Subtotal</span>
    <span>{{ number_format((float)$subtotal, 2, '.', ',') }}</span>
  </div>
  <div class="line">
    <span>IVA ({{ isset($order->tax_rate) ? number_format($order->tax_rate*100,2) : '16.00' }}%)</span>
    <span>{{ number_format((float)$tax, 2, '.', ',') }}</span>
  </div>
  <div class="line grand">
    <span>Total</span>
    <span>{{ number_format((float)$total, 2, '.', ',') }}</span>
  </div>
</div>

  </div>
</body>
</html>
