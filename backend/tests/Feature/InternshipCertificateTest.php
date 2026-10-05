<?php

namespace Tests\Feature;

use App\Models\InternshipCertificate;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class InternshipCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_admin_can_issue_and_candidate_can_download_only_with_matching_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'web');

        $this->post('/api/v1/admin/internship-certificates', [
            'candidate_name' => 'Candidate One',
            'program_title' => 'Electrical Engineering Internship',
            'mobile' => '9876543210',
            'date_of_birth' => '2000-01-15',
            'certificate' => UploadedFile::fake()->create('certificate.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonMissingPath('data.lookup_hash')->assertJsonMissingPath('data.file_path');

        $this->assertDatabaseCount('internship_certificates', 1);
        $certificate = InternshipCertificate::firstOrFail();
        Storage::disk('local')->assertExists($certificate->file_path);

        $this->postJson('/api/v1/internships/certificates/download', ['mobile' => '9876543210', 'date_of_birth' => '2000-01-16'])->assertNotFound();
        $this->postJson('/api/v1/internships/certificates/download', ['mobile' => '9876543211', 'date_of_birth' => '2000-01-15'])->assertNotFound();
        $this->postJson('/api/v1/internships/certificates/download', ['mobile' => '9876543210', 'date_of_birth' => '2000-01-15'])->assertOk()->assertDownload('E4ENGINEERS-Internship-Certificate.pdf');

        $this->patchJson("/api/v1/admin/internship-certificates/{$certificate->id}", ['is_active' => false])->assertOk();
        $this->postJson('/api/v1/internships/certificates/download', ['mobile' => '9876543210', 'date_of_birth' => '2000-01-15'])->assertNotFound();
    }

    public function test_guest_cannot_upload_certificate(): void
    {
        $this->postJson('/api/v1/admin/internship-certificates', [])->assertUnauthorized();
    }

    public function test_admin_can_correct_candidate_lookup_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $certificate = InternshipCertificate::create([
            'candidate_name' => 'Candidate Two',
            'program_title' => 'Mechanical Internship',
            'mobile_last_four' => '3210',
            'lookup_hash' => InternshipCertificate::lookupHash('9876543210', '2000-01-15'),
            'file_path' => 'internship-certificates/candidate-two.pdf',
            'file_name' => 'E4ENGINEERS-Internship-Certificate.pdf',
        ]);
        Storage::disk('local')->put($certificate->file_path, '%PDF-test');

        $this->actingAs($admin, 'web')->patchJson("/api/v1/admin/internship-certificates/{$certificate->id}", [
            'mobile' => '9876543211', 'date_of_birth' => '2000-01-16',
        ])->assertOk()->assertJsonPath('data.mobile_last_four', '3211');

        $this->postJson('/api/v1/internships/certificates/download', ['mobile' => '9876543210', 'date_of_birth' => '2000-01-15'])->assertNotFound();
        $this->postJson('/api/v1/internships/certificates/download', ['mobile' => '9876543211', 'date_of_birth' => '2000-01-16'])->assertOk();
    }
}
