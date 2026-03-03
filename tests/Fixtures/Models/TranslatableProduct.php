<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Fixtures\Models;

use Canvastack\Canvastack\Support\Localization\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * TranslatableProduct Model.
 *
 * Test fixture for testing the Translatable trait.
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property float $price
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TranslatableProduct extends Model
{
    use Translatable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translatable_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Translatable attributes.
     *
     * @var array<string>
     */
    protected array $translatable = [
        'name',
        'description',
    ];
}
