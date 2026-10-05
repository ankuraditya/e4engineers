<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InternshipCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternshipCertificateController extends Controller
{
    public function download(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
        ]);

        $certificate = InternshipCertificate::where('lookup_hash', InternshipCertificate::lookupHash($data['mobile'], $data['date_of_birth']))
            ->where('is_active', true)->first();

        abort_unless($certificate && Storage::disk('local')->exists($certificate->file_path), 404, 'No certificate was found for those details.');

        return Storage::disk('local')->download($certificate->file_path, $certificate->file_name, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store']);
    }
}
