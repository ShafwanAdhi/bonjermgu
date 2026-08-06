<?php

namespace App\Domain\Application;

/**
 * There is no third status. "Tidak berlaku" is expressed by the absence of a
 * row, not by a value (docs/document-requirement.md section 4).
 */
enum DocumentStatus: string
{
    case Belum = 'Belum';
    case Lengkap = 'Lengkap';

    public function isComplete(): bool
    {
        return $this === self::Lengkap;
    }
}
