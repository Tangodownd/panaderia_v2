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

    /**
     * Get the carts that contain this product.
     */
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
}

