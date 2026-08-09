<?php

namespace App\Http\Controllers;

use App\Support\CreditSimulationPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SimulationController extends Controller
{
    public function print(Request $request): View|RedirectResponse
    {
        $snapshot = $this->snapshotOrRedirect($request);

        if ($snapshot instanceof RedirectResponse) {
            return $snapshot;
        }

        $subject = $this->subject($snapshot);

        return view('referral.simulation-print', [
            'subject' => $subject,
            'results' => $snapshot['results'],
            'disbursementHeading' => $snapshot['disbursement_heading'],
        ]);
    }

    public function download(Request $request, CreditSimulationPdf $pdf): Response|RedirectResponse
    {
        $snapshot = $this->snapshotOrRedirect($request);

        if ($snapshot instanceof RedirectResponse) {
            return $snapshot;
        }

        $subject = $this->subject($snapshot);
        $filename = $pdf->filename($subject);

        return response($pdf->render($subject, $snapshot['results'], $snapshot['disbursement_heading']), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function snapshotOrRedirect(Request $request): array|RedirectResponse
    {
        $snapshot = $request->session()->get('simulation.active');

        if (! is_array($snapshot) || ($snapshot['owner_user_id'] ?? null) !== $request->user()->id) {
            return redirect()
                ->route('simulation')
                ->with('simulation_error', 'Jalankan simulasi terlebih dahulu sebelum mengunduh.');
        }

        $requiredIdentityFields = ['debtor_name'];

        if ($this->requiresPersonalDebtorIdentity($snapshot)) {
            $requiredIdentityFields[] = 'debtor_nik';
            $requiredIdentityFields[] = 'debtor_birth_date';
        }

        foreach ($requiredIdentityFields as $identityField) {
            if (blank($snapshot['subject'][$identityField] ?? null)) {
                return redirect()
                    ->route('simulation')
                    ->with('simulation_error', 'Lengkapi data calon debitur melalui tombol download pada hasil simulasi.');
            }
        }

        return $snapshot;
    }

    private function requiresPersonalDebtorIdentity(array $snapshot): bool
    {
        $subject = $snapshot['subject'] ?? [];

        return ($subject['debtor_type_value'] ?? null) !== 'legal_entity'
            && ($subject['debtor_type'] ?? null) !== 'Badan Hukum Usaha';
    }

    private function subject(array $snapshot): array
    {
        $subject = $snapshot['subject'];
        $subject['printed_at'] = now()->locale('id')->translatedFormat('d F Y H.i');

        return $subject;
    }
}
