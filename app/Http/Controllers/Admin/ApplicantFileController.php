<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantFileController extends Controller
{
    public function photo(Registration $registration): StreamedResponse
    {
        return $this->stream($registration->photo_path, false);
    }

    public function downloadPhoto(Registration $registration): StreamedResponse
    {
        return $this->stream($registration->photo_path, true, $registration->registration_number.'-photo');
    }

    public function proof(Registration $registration): StreamedResponse
    {
        return $this->stream($registration->payment_proof_path, false);
    }

    public function downloadProof(Registration $registration): StreamedResponse
    {
        return $this->stream($registration->payment_proof_path, true, $registration->registration_number.'-payment-proof');
    }

    private function stream(?string $path, bool $download, ?string $basename = null): StreamedResponse
    {
        abort_unless(filled($path) && Storage::disk('local')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $name = ($basename ?: 'file').($extension ? '.'.$extension : '');

        if ($download) {
            return Storage::disk('local')->download($path, $name);
        }

        return Storage::disk('local')->response($path);
    }
}
