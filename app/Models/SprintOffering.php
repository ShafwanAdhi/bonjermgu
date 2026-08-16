<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One valid Product ID / Product Offering pair extracted from the SPRINT
 * workbook. The web screen filters these rows instead of inventing a pair that
 * may not exist in head office's dropdowns.
 */
class SprintOffering extends Model
{
    protected $fillable = [
        'fingerprint',
        'source_workbook',
        'source_sheet',
        'source_row',
        'source_channel',
        'product_id',
        'product_offering',
        'product_category',
        'channel',
        'region',
        'unit',
        'brand',
        'profile',
        'debtor_type',
        'dp',
        'tenor',
        'instalment',
    ];

    /** @param  Builder<self>  $query */
    public function scopeOrderedForSprint(Builder $query): void
    {
        $query
            ->orderBy('product_id')
            ->orderBy('product_offering')
            ->orderBy('source_sheet')
            ->orderBy('source_row');
    }
}
