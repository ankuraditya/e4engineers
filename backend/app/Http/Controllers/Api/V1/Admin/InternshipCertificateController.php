<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InternshipCertificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InternshipCertificateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => InternshipCertificate::latest()->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'candidate_name' => ['required', 'string', 'max:150'],
            'program_title' => ['required', 'string', 'max:200'],
            'mobile' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'certificate' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);
        $hash = InternshipCertificate::lookupHash($data['mobile'], $data['date_of_birth']);
        abort_if(InternshipCertificate::where('lookup_hash', $hash)->exists(), 422, 'A certificate already exists for this phone number and date of birth.');

        $path = $request->file('certificate')->store('internship-certificates', 'local');
        try {
            $certificate = InternshipCertificate::create([
                'candidate_name' => $data['candidate_name'],
                'program_title' => $data['program_title'],
                'mobile_last_four' => substr($data['mobile'], -4),
                'lookup_hash' => $hash,
                'file_path' => $path,
                'file_name' => 'E4ENGINEERS-Internship-Certificate.pdf',
                'created_by' => $request->user()->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['data' => $certificate, 'message' => 'Certificate added.'], 201);
    }

    public function update(Request $request, InternshipCertificate $certificate): JsonResponse
    {
        $data = $request->validate([
            'candidate_name' => ['sometimes', 'required', 'string', 'max:150'],
            'program_title' => ['sometimes', 'required', 'string', 'max:200'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'certificate' => ['sometimes', 'file', 'mimes:pdf', 'max:5120'],
            'mobile' => ['nullable', 'required_with:date_of_birth', 'regex:/^[6-9][0-9]{9}$/'],
            'date_of_birth' => ['nullable', 'required_with:mobile', 'date_format:Y-m-d', 'before:today'],
        ]);
        if (! empty($data['mobile']) && ! empty($data['date_of_birth'])) {
            $hash = InternshipCertificate::lookupHash($data['mobile'], $data['date_of_birth']);
            abort_if(InternshipCertificate::where('lookup_hash', $hash)->where('id', '!=', $certificate->id)->exists(), 422, 'A certificate already exists for this phone number and date of birth.');
            $data['lookup_hash'] = $hash;
            $data['mobile_last_four'] = substr($data['mobile'], -4);
        }
        unset($data['mobile'], $data['date_of_birth']);
        $oldPath = $certificate->file_path;
        if ($request->hasFile('certificate')) {
            $data['file_path'] = $request->file('certificate')->store('internship-certificates', 'local');
        }
        unset($data['certificate']);
        try {
            $certificate->update($data);
        } catch (\Throwable $exception) {
            if (isset($data['file_path'])) {
                Storage::disk('local')->delete($data['file_path']);
            }
            throw $exception;
        }
        if (isset($data['file_path'])) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json(['data' => $certificate->refresh(), 'message' => 'Certificate updated.']);
    }

    public function destroy(InternshipCertificate $certificate): JsonResponse
    {
        $path = $certificate->file_path;
        $certificate->delete();
        Storage::disk('local')->delete($path);

        return response()->json(['message' => 'Certificate deleted.']);
    }
}
