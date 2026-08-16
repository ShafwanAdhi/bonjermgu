<?php

use App\Repositories\SimulationConfigurationRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Retires simulation configs cached before ProductConfig grew its card rates.
 *
 * forProduct() caches the whole SimulationConfig for twelve hours, so a payload
 * serialised by the previous release deserialises into a ProductConfig with no
 * flatRatesAddb or flatRatesAddm at all. Reading either throws "must not be
 * accessed before initialization", and every simulation fails until the entry
 * expires. Found while running the engine against the workbook right after the
 * columns landed.
 *
 * The version key already exists for exactly this, but only Admin edits bump
 * it; a deploy that changes the shape of the cached object does not. Bumping it
 * here means the new code never meets an old payload.
 */
return new class extends Migration
{
    public function up(): void
    {
        Cache::forever(SimulationConfigurationRepository::CACHE_VERSION_KEY, (string) hrtime(true));
    }

    /**
     * Rolling back leaves the newer key in place on purpose. Restoring the old
     * one would hand the previous release a payload it cannot read either.
     */
    public function down(): void {}
};
