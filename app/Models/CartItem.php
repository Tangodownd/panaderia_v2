<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
    ];

    /**
     * Obtener el carrito al que pertenece este item
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Obtener el producto de este item
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

