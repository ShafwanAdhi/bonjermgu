<?php

namespace App\Repositories;

use App\Models\AgeGroup;
use App\Models\Domicile;
use App\Models\Institution;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class MasterLookupRepository
{
    public const CACHE_VERSION_KEY = 'master_lookup_version';

    /** @return Collection<int, Domicile> */
    public function domiciles(): Collection
    {
        return $this->remember('domiciles', fn () => Domicile::query()
            ->orderBy('sort_order')
            ->get(['id', 'name']));
    }

    /** @return Collection<int, AgeGroup> */
    public function ageGroups(): Collection
    {
        return $this->remember('age_groups', fn () => AgeGroup::query()
            ->orderBy('sort_order')
            ->get(['id', 'label']));
    }

    /** @return Collection<int, ReferralCategory> */
    public function activeReferralCategories(): Collection
    {
        return $this->remember('active_referral_categories', fn () => $this->sortOptionsWithOthersLast(
            ReferralCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        ));
    }

    /** @return Collection<int, ReferralSubCategory> */
    public function subCategoriesForCategory(int $categoryId): Collection
    {
        return $this->remember("category:{$categoryId}:sub_categories", fn () => $this->sortOptionsWithOthersLast(
            ReferralSubCategory::query()
                ->where('category_id', $categoryId)
                ->orderBy('name')
                ->get(['id', 'name'])
        ));
    }

    /** @return Collection<int, Institution> */
    public function institutionsForSubCategory(int $subCategoryId): Collection
    {
        return $this->remember("sub_category:{$subCategoryId}:institutions", fn () => $this->sortOptionsWithOthersLast(
            Institution::query()
                ->where('sub_category_id', $subCategoryId)
                ->orderBy('name')
                ->get(['id', 'name'])
        ));
    }

    /** @param callable(): Collection<int, mixed> $callback */
    private function remember(string $key, callable $callback): Collection
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, '0');

        return Cache::remember(
            "master_lookup:{$version}:{$key}",
            now()->addHours(12),
            $callback,
        );
    }

    /** @template T of object @param Collection<int, T> $options @return Collection<int, T> */
    private function sortOptionsWithOthersLast(Collection $options): Collection
    {
        return $options
            ->sortBy(fn ($option) => sprintf(
                '%d-%s',
                $this->isOthersLabel($option->name) ? 1 : 0,
                Str::lower($option->name),
            ))
            ->values();
    }

    private function isOthersLabel(string $name): bool
    {
        return in_array(Str::lower($name), ['others', 'other', 'lainnya'], true);
    }
}
