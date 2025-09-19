<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image',
        'category_id',
        'discount',
        'rating'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function carts()
    {
        return $this->belongsToMany(Cart::class, 'cart_items')
                    ->withPivot('quantity', 'price')
                    ->withTimestamps();
    }

    /**
     * Decrease the product stock by the specified quantity
     *
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock($quantity = 1)
    {
        if ($this->stock >= $quantity) {
            $this->stock -= $quantity;
            return $this->save();
        }
        return false;
    }
    
    /**
     * Verificar si el producto tiene stock disponible
     *
     * @return bool
     */
    public function hasStock()
    {
        return $this->stock > 0;
    }

    /**
     * Verificar si hay suficiente stock para una cantidad dada
     *
     * @param  int  $quantity
     * @return bool
     */
    public function hasStockFor($quantity)
    {
        return $this->stock >= $quantity;
    }

    /**
     * Scope para filtrar productos con stock
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
    
    public function availableStock(): int {
    $reserved = \App\Models\StockReservation::where('product_id', $this->id)
        ->where('status', 'reserved')
        ->where('expires_at', '>', now())
        ->sum('quantity');
    return max(0, (int)$this->stock - (int)$reserved);
    }

}