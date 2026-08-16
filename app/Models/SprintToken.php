<?php

namespace App\Models;

use App\Models\Concerns\AuditsAdminChanges;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * One dimension of a SPRINT Product ID / Product Offering code.
 *
 * @property string $group_key
 * @property string $source
 * @property string|null $product_token
 * @property string|null $offering_token
 */
class SprintToken extends Model
{
    use AuditsAdminChanges;

    /**
     * Segment order of each code, following Master!C5 and Master!C6. A group
     * missing from a list genuinely has no segment in that code: brand appears
     * only in the Offering, and the Product ID names no region, DP, or tenor.
     */
    public const PRODUCT_ID_PARTS = ['product', 'channel', 'unit', 'profile', 'debtor_type'];

    public const OFFERING_PARTS = ['product', 'region', 'channel', 'unit', 'brand', 'profile', 'debtor_type', 'dp', 'tenor'];

    /**
     * Every group, in the order the Admin screen shows them. `channel_source`
     * is the odd one out: it spells no segment, it maps the referral
     * sub-category the AO already picked onto one of the channels above.
     */
    public const GROUPS = [
        'product' => 'Product',
        'channel' => 'Kanal',
        'unit' => 'Jenis Kendaraan',
        'brand' => 'Brand',
        'profile' => 'Profil Debitur',
        'debtor_type' => 'Type Debitur',
        'dp' => 'Golongan DP',
        'tenor' => 'Tenor',
        'region' => 'Wilayah',
        'instalment' => 'Jenis Angsuran',
        'channel_source' => 'Sub Kategori ke Kanal',
    ];

    protected $fillable = ['group_key', 'source', 'product_token', 'offering_token', 'position'];

    /**
     * Every token, grouped and ordered. Small enough (a few dozen rows) that one
     * query beats a query per dropdown.
     *
     * @return array<string, Collection<int, self>>
     */
    public static function grouped(): array
    {
        return static::query()
            ->orderBy('group_key')
            ->orderBy('position')
            ->get()
            ->groupBy('group_key')
            ->all();
    }
}
