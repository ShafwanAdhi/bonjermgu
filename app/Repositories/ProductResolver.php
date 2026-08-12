<?php

namespace App\Repositories;

use App\Domain\Simulation\VehicleUsage;
use App\Models\Product;
use App\Models\Referral;
use App\Models\ReferralCategory;
use RuntimeException;

final class ProductResolver
{
    public function resolve(Referral $referral, VehicleUsage $usage): Product
    {
        $category = $referral->category()
            ->where('is_active', true)
            ->first();

        if ($category === null) {
            throw new RuntimeException('Kategori Referral aktif tidak ditemukan.');
        }

        return $this->resolveForCategory($category, $usage);
    }

    /**
     * Same resolution, but from the category itself. The Account Officer screen
     * picks the Referral category by hand because an AO has no category of its
     * own, and the Product must still come out of the same mapping a Referral
     * would hit — otherwise the two screens could quietly disagree.
     */
    public function resolveForCategory(ReferralCategory $category, VehicleUsage $usage): Product
    {
        if (! $category->allowsVehicleUsage($usage)) {
            throw new RuntimeException(
                "Penggunaan kendaraan {$usage->value} tidak tersedia untuk kategori Referral '{$category->name}'."
            );
        }

        $productName = $this->nameFor($category, $usage);

        $product = Product::query()
            ->where('name', $productName)
            ->where('is_active', true)
            ->with(['rates' => fn ($query) => $query->orderBy('tenor_months')])
            ->first();

        if ($product === null) {
            throw new RuntimeException("Product aktif '{$productName}' tidak ditemukan.");
        }

        return $product;
    }

    public function nameFor(ReferralCategory $category, VehicleUsage $usage): string
    {
        return implode(' ', [
            $category->segment,
            $usage->value,
            $category->tier,
        ]);
    }
}
