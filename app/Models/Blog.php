<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'contenido',
        'category_id',
        'precio',
        'descuento',
        'valoracion',
        'stock',
        'etiquetas',
        'brand',
        'sku',
        'weight',
        'dimensions',
        'warrantyInformation',
        'shippingInformation',
        'availabilityStatus',
        'reviews',
        'returnPolicy',
        'minimumOrderQuantity',
        'thumbnail',
        'barcode'
    ];

    protected $casts = [
        'etiquetas' => 'array',
        'dimensions' => 'array',
        'reviews' => 'array',
        'precio' => 'float',
        'descuento' => 'float',
        'valoracion' => 'float',
        'stock' => 'integer',
        'weight' => 'float',
        'minimumOrderQuantity' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}