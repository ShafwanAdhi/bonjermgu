<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRate extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['product_id', 'tenor_months', 'effective_rate'];

    protected function casts(): array
    {
        return [
            'tenor_months' => 'integer',
            'effective_rate' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
