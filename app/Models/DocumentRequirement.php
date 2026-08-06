<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catalogue of 26 requirements. The primary key is the stable code — status
 * rows reference it, so it must never change (docs/data-model.md section 6).
 */
class DocumentRequirement extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'subject',
        'group_name',
        'sort_order',
    ];
}
