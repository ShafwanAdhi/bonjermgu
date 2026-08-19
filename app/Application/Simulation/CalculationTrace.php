<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\Output\TenorResult;
use App\Domain\Simulation\Output\ZeroReason;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\SimulationProfile;
use App\Support\Format;

/**
 * Turns one TenorResult into an ordered, readable derivation.
 *
 * Presentation only. It reads what the engine already produced and never
 * recomputes anything — a trace that did its own arithmetic could agree with
 * itself while disagreeing with the engine, which is the one failure mode a
 * verification screen must not have.
 *
 * Every step carries the formula alongside the value, because the point is to
 * let Admin check a figure against docs/credit-simulation.md, not just read it.
 */
final class CalculationTrace
{
    /**
     * @return array<int, array{
     *     title: string,
     *     note: string|null,
     *     steps: array<int, array{label: string, formula: string|null, value: string, emphasis?: bool}>
     * }>
     */
    public static function build(TenorResult $tenor, ConfigurationSimulationOutcome $outcome): array
    {
        $input = $outcome->input;
        $config = $outcome->config;
        $isUcf = $input->financingType === FinancingType::UCF;
        $isModeA = $input->mode === SimulationMode::A;
        $isOfficer = $config->profile === SimulationProfile::OFFICER;

        if ($tenor->zeroReason !== null) {
            return [self::blockedSection($tenor, $outcome)];
        }

        return array_values(array_filter([
            self::priceSection($tenor, $isUcf, $isOfficer),
            self::downPaymentSection($tenor, $isOfficer),
            self::rateSection($tenor, $input->instalmentType, $config),
            self::insuranceSection($tenor),
            self::feeSection($tenor, $config),
            self::instalmentSection($tenor, $input->instalmentType, $isModeA),
            $isModeA ? self::disbursementSection($tenor, $config, $isUcf) : self::desiredAmountSection($tenor, $config, $isUcf),
            self::refundSection($tenor),
            self::outcomeSection($tenor, $config, $isUcf, $isModeA),
        ]));
    }

    /** A tenor that produces nothing must say why, not just show zeros. */
    private static function blockedSection(TenorResult $tenor, ConfigurationSimulationOutcome $outcome): array
    {
        $reason = match ($tenor->zeroReason) {
            ZeroReason::RateUnavailable => 'Rate tenor ini kosong pada Product terpilih. Kosong berarti tenor tidak tersedia — bukan rate 0%.',
            ZeroReason::NotEligible => sprintf(
                'Usia unit melebihi batas di akhir tenor. Batas usia maksimal unit %d tahun, kendaraan tahun %d, tahun berjalan %d.',
                $outcome->config->maxVehicleAge,
                $outcome->input->vehicleYear,
                $outcome->currentYear,
            ),
            ZeroReason::PriceUnavailable => 'Harga kendaraan tidak tersedia pada master PHPM untuk tahun yang dipilih.',
            ZeroReason::DownPaymentExceedsPrice => 'Deviasi mendorong Net DP mencapai atau melampaui harga unit, sehingga tidak ada nilai yang dapat dibiayai.',
            ZeroReason::DownPaymentBelowMinimum => 'Nominal yang dikehendaki menghasilkan DP Net di bawah Net DP minimum yang disyaratkan.',
            null => 'Tenor tidak menghasilkan pembiayaan.',
        };

        return [
            'title' => 'Tenor tidak menghasilkan pembiayaan',
            'note' => $reason,
            'steps' => [
                [
                    'label' => 'Seluruh komponen',
                    'formula' => 'Tenor gugur → semua nilai 0, termasuk refund',
                    'value' => Format::rupiah(0),
                    'emphasis' => true,
                ],
            ],
        ];
    }

    private static function priceSection(TenorResult $tenor, bool $isUcf, bool $isOfficer): array
    {
        return [
            'title' => '1 · Harga dan Deviasi',
            'note' => $isUcf
                ? 'Harga PHPM dan Harga OTR adalah dua nilai berbeda. PHPM mentah adalah pembanding sekaligus pembagi Deviasi; Harga OTR dipakai untuk Net DP, LTV, Sum Insured, dan ACP.'
                : 'Harga PHPM dan Harga OTR adalah dua nilai berbeda. PHPM mentah dipakai untuk Deviasi dan ACP; Harga OTR dipakai untuk Net DP, LTV, dan Sum Insured.',
            'steps' => array_values(array_filter([
                [
                    'label' => 'Harga PHPM',
                    'formula' => 'master harga kendaraan, tidak dibulatkan',
                    'value' => Format::rupiah((int) round($tenor->phpmPrice)),
                ],
                [
                    'label' => 'Harga OTR',
                    'formula' => match (true) {
                        $isUcf => 'Harga Pasar yang diinput',
                        $isOfficer => 'Harga Taksasi yang diinput',
                        default => 'ROUNDDOWN(PHPM, ratusan)',
                    },
                    'value' => Format::rupiah((int) round($tenor->otrPrice)),
                ],
                [
                    'label' => 'Deviasi',
                    'formula' => 'MAX(OTR − PHPM, 0)',
                    'value' => Format::rupiah((int) round($tenor->deviationAmount)),
                ],
                [
                    'label' => 'Deviasi (%)',
                    'formula' => 'Deviasi ÷ PHPM',
                    'value' => Format::percent($tenor->deviationRate),
                ],
            ])),
        ];
    }

    private static function downPaymentSection(TenorResult $tenor, bool $isOfficer): array
    {
        return [
            'title' => '2 · Net DP dan LTV',
            'note' => 'Deviasi menambah persentase Net DP minimal.',
            'steps' => [
                [
                    'label' => 'Net DP minimal',
                    'formula' => 'ketentuan produk + deviasi (%)',
                    'value' => Format::percent($tenor->minimumNetDpRate),
                ],
                [
                    'label' => 'Net DP',
                    'formula' => $isOfficer ? 'ROUNDUP(OTR × Net DP (%), ribuan)' : 'OTR × Net DP (%)',
                    'value' => Format::rupiah((int) round($tenor->netDpAmount)),
                ],
                [
                    'label' => 'LTV (%)',
                    'formula' => '1 − Net DP (%)',
                    'value' => Format::percent($tenor->ltvRate),
                ],
                [
                    'label' => 'LTV',
                    'formula' => 'OTR − Net DP',
                    'value' => Format::rupiah((int) round($tenor->ltvAmount)),
                    'emphasis' => true,
                ],
            ],
        ];
    }

    private static function rateSection(TenorResult $tenor, InstalmentType $instalmentType, $config): array
    {
        $upping = $tenor->flatRateFinal - $tenor->flatRate;

        return [
            'title' => '3 · Rate',
            'note' => sprintf(
                'Effective rate dikonversi ke flat rate memakai PMT %s, lalu ditambah upping rate Product.',
                $instalmentType === InstalmentType::ADDM ? 'ADDM (bayar di muka)' : 'ADDB (bayar di belakang)',
            ),
            'steps' => [
                [
                    'label' => 'Effective rate p.a.',
                    'formula' => 'rate Product pada tenor ini',
                    'value' => self::rate($tenor->effectiveRate),
                ],
                [
                    'label' => 'Flat rate',
                    'formula' => 'konversi PMT dari effective rate',
                    'value' => self::rate($tenor->flatRate),
                ],
                [
                    'label' => 'Upping rate',
                    'formula' => 'up_rate pada Product',
                    'value' => self::rate($upping),
                ],
                [
                    'label' => 'Flat rate final',
                    'formula' => 'flat rate + upping',
                    'value' => self::rate($tenor->flatRateFinal),
                ],
                [
                    'label' => 'Bunga jual',
                    'formula' => 'flat rate final × (tenor ÷ 12)',
                    'value' => self::rate($tenor->sellingInterestRate),
                    'emphasis' => true,
                ],
            ],
        ];
    }

    private static function insuranceSection(TenorResult $tenor): array
    {
        $insurance = $tenor->insurance;

        $rows = [
            ['Casco / TLO', 'rate band harga × Sum Insured', $insurance->casco],
            ['Loading usia', 'rate loading × premi dasar', $insurance->loading],
            ['Perluasan', 'banjir, gempa, huru-hara, terorisme', $insurance->extensions],
            ['TJH', 'lapisan tanggung jawab hukum', $insurance->tjh],
            ['Pengemudi', 'santunan pengemudi', $insurance->driver],
            ['Penumpang', 'santunan penumpang × jumlah', $insurance->passenger],
            ['ACP', 'asuransi jiwa debitur', $insurance->acp],
            ['Garansi mesin', 'biaya tetap bila diaktifkan', $insurance->engineWarranty],
        ];

        $steps = [];

        foreach ($rows as [$label, $formula, $value]) {
            $steps[] = [
                'label' => $label,
                'formula' => $formula,
                'value' => Format::rupiah((int) round($value)),
            ];
        }

        $steps[] = [
            'label' => 'Total asuransi',
            'formula' => 'jumlah seluruh komponen, dibulatkan',
            'value' => Format::rupiah($insurance->total),
            'emphasis' => true,
        ];

        return [
            'title' => '4 · Asuransi',
            'note' => 'Komponen bernilai nol berarti tidak diaktifkan untuk simulasi ini. Loading, Perluasan, TJH, Pengemudi, dan Penumpang hanya ditagih pada tahun dengan coverage Comprehensive.',
            'steps' => $steps,
        ];
    }

    private static function feeSection(TenorResult $tenor, $config): array
    {
        return [
            'title' => '5 · Biaya',
            'note' => 'Administrasi memakai Admin Maksimal ditambah upping admin. Admin Minimal tidak dipakai perhitungan.',
            'steps' => [
                [
                    'label' => 'Provisi',
                    'formula' => '(provisi + upping provisi) × LTV',
                    'value' => Format::rupiah((int) round($tenor->fees->provision)),
                ],
                [
                    'label' => 'Administrasi',
                    'formula' => 'Admin Maksimal + upping admin',
                    'value' => Format::rupiah((int) round($tenor->fees->administration)),
                ],
                [
                    'label' => 'Fiducia',
                    'formula' => 'berjenjang menurut nilai',
                    'value' => Format::rupiah((int) round($tenor->fees->fiducia)),
                ],
                [
                    'label' => 'BELIV',
                    'formula' => 'biaya tetap bila BELIV aktif',
                    'value' => Format::rupiah((int) round($tenor->fees->beliv)),
                ],
                [
                    'label' => 'Total biaya',
                    'formula' => 'provisi + administrasi + fiducia + BELIV',
                    'value' => Format::rupiah((int) round($tenor->fees->total())),
                    'emphasis' => true,
                ],
            ],
        ];
    }

    private static function instalmentSection(TenorResult $tenor, InstalmentType $instalmentType, bool $isModeA): array
    {
        return [
            'title' => '6 · Angsuran',
            'note' => 'Angsuran dibulatkan ke atas ke ribuan.',
            'steps' => array_values(array_filter([
                [
                    'label' => 'Bunga',
                    'formula' => 'LTV × bunga jual',
                    'value' => Format::rupiah((int) round($tenor->interestAmount)),
                ],
                [
                    'label' => 'Total A/R',
                    'formula' => 'LTV + bunga',
                    'value' => Format::rupiah((int) round($tenor->totalAccountsReceivable)),
                ],
                [
                    'label' => 'Angsuran',
                    'formula' => $isModeA
                        ? 'ROUNDUP(Total A/R ÷ tenor, ribuan)'
                        : 'ROUNDUP(basis × (1 + bunga jual) ÷ pembagi, ribuan)',
                    'value' => Format::rupiah($tenor->instalment),
                    'emphasis' => true,
                ],
                $instalmentType === InstalmentType::ADDM ? [
                    'label' => 'Angsuran pertama',
                    'formula' => 'ADDM — angsuran pertama dibayar di muka',
                    'value' => Format::rupiah((int) round($tenor->firstInstalment)),
                ] : null,
            ])),
        ];
    }

    private static function disbursementSection(TenorResult $tenor, $config, bool $isUcf): array
    {
        return [
            'title' => '7 · Pembayaran Pertama dan Pencairan',
            'note' => null,
            'steps' => [
                [
                    'label' => 'Total bayar pertama',
                    'formula' => 'Net DP + asuransi + biaya + angsuran pertama',
                    'value' => Format::rupiah((int) round($tenor->firstPayment)),
                ],
                [
                    'label' => 'Pencairan gross',
                    'formula' => 'OTR − total bayar pertama',
                    'value' => Format::rupiah((int) round($tenor->grossDisbursement)),
                ],
                [
                    'label' => 'Potongan pencairan',
                    'formula' => 'BBNKB, PKB, faktur',
                    'value' => Format::rupiah((int) round($config->statutoryDisbursementDeductions())),
                ],
                [
                    'label' => 'Sisa kewajiban',
                    'formula' => 'pelunasan kewajiban berjalan',
                    'value' => Format::rupiah((int) round($config->currentOutstandingObligationAmount())),
                ],
                [
                    'label' => 'Sisa OS LK sebelumnya',
                    'formula' => 'pelunasan outstanding sebelumnya',
                    'value' => Format::rupiah((int) round($config->currentPreviousOutstandingPrincipalAmount())),
                ],
                [
                    'label' => 'Deposit angsuran',
                    'formula' => sprintf(
                        '%d angsuran × %s',
                        $config->depositInstalmentCount,
                        Format::rupiah($tenor->instalment),
                    ),
                    'value' => Format::rupiah((int) round($tenor->depositInstalmentAmount)),
                ],
                [
                    'label' => 'Pencairan neto',
                    'formula' => 'pencairan gross - seluruh potongan - deposit',
                    'value' => Format::rupiah((int) round($tenor->netDisbursement)),
                    'emphasis' => true,
                ],
            ],
        ];
    }

    private static function desiredAmountSection(TenorResult $tenor, $config, bool $isUcf): array
    {
        $steps = [
            [
                'label' => 'Nominal dikehendaki',
                'formula' => 'input pengguna',
                'value' => Format::rupiah((int) round($tenor->desiredAmount)),
            ],
            [
                'label' => 'Total bayar pertama',
                'formula' => 'asuransi + biaya',
                'value' => Format::rupiah((int) round($tenor->firstPayment)),
            ],
            [
                'label' => 'Net DP hasil',
                'formula' => 'nominal - (bayar pertama + angsuran pertama)',
                'value' => Format::rupiah((int) round($tenor->netDpAmount)),
                'emphasis' => true,
            ],
        ];

        if (! $isUcf) {
            $steps[] = [
                'label' => 'Sisa kewajiban',
                'formula' => 'mengurangi pencairan',
                'value' => Format::rupiah((int) round($config->currentOutstandingObligationAmount())),
            ];
            $steps[] = [
                'label' => 'Sisa OS LK sebelumnya',
                'formula' => 'mengurangi pencairan',
                'value' => Format::rupiah((int) round($config->currentPreviousOutstandingPrincipalAmount())),
            ];
        }

        return [
            'title' => '7 · Nominal Dikehendaki',
            'note' => 'Mode B menghitung angsuran dari nominal yang dikehendaki, bukan sebaliknya.',
            'steps' => $steps,
        ];
    }

    private static function refundSection(TenorResult $tenor): array
    {
        $refund = $tenor->refund;

        return [
            'title' => '8 · Refund',
            'note' => 'Refund dibayarkan terpisah dan tidak menambah pencairan. Dana Tunai hanya memperoleh Refund Bunga dan Refund Provisi.',
            'steps' => [
                [
                    'label' => 'Refund asuransi',
                    'formula' => 'premi yang dapat dikembalikan × persentase',
                    'value' => Format::rupiah((int) round($refund->insurance)),
                ],
                [
                    'label' => 'Refund bunga',
                    'formula' => '(LTV × upping × tahun) ÷ (1 + bunga jual) × persentase',
                    'value' => Format::rupiah((int) round($refund->interest)),
                ],
                [
                    'label' => 'Refund provisi',
                    'formula' => 'biaya provisi × persentase',
                    'value' => Format::rupiah((int) round($refund->provision)),
                ],
                [
                    'label' => 'Refund administrasi',
                    'formula' => 'upping admin × persentase',
                    'value' => Format::rupiah((int) round($refund->administration)),
                ],
                [
                    'label' => 'Total refund',
                    'formula' => 'jumlah komponen refund',
                    'value' => Format::rupiah($refund->total),
                    'emphasis' => true,
                ],
            ],
        ];
    }

    private static function outcomeSection(TenorResult $tenor, $config, bool $isUcf, bool $isModeA): array
    {
        $heading = match (true) {
            ! $isUcf && $isModeA => 'Pencairan Maksimal',
            ! $isUcf => 'Pencairan',
            $isModeA => 'Pencairan Neto',
            default => 'Total DP',
        };

        return [
            'title' => '9 · Hasil',
            'note' => 'Nilai inilah yang tampil pada tabel lima tenor.',
            'steps' => [
                [
                    'label' => $heading,
                    'formula' => match (true) {
                        $isModeA => 'pencairan neto + total refund',
                        ! $isUcf && $config->disbursementDeductions() > 0 => 'nominal dikehendaki - potongan',
                        default => 'nominal dikehendaki',
                    },
                    'value' => Format::rupiah((int) round($tenor->outputAmount)),
                    'emphasis' => true,
                ],
                [
                    'label' => 'Angsuran',
                    'formula' => 'hasil bagian 6',
                    'value' => Format::rupiah($tenor->instalment),
                    'emphasis' => true,
                ],
            ],
        ];
    }

    /** Rates are shown at full precision — rounding here would hide a mismatch. */
    private static function rate(float $value): string
    {
        return number_format($value * 100, 6, ',', '.').'%';
    }
}
