<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRate extends Model
{
    use AuditsAdminChanges;

    public $timestamps = false;

    protected $fillable = ['product_id', 'tenor_months', 'effective_rate', 'flat_rate_addb', 'flat_rate_addm'];

    protected function casts(): array
    {
        return [
            'tenor_months' => 'integer',
            'effective_rate' => 'float',
            'flat_rate_addb' => 'float',
            'flat_rate_addm' => 'float',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
