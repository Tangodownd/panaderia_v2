<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model {
  protected $fillable = ['product_id','quantity','cart_id','session_id','order_id','status','expires_at'];
  protected $casts = ['expires_at'=>'datetime'];
}
