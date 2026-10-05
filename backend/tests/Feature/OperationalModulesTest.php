<?php

namespace Tests\Feature;

use App\Models\JobOpening;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_enquiry_is_persisted_and_idempotent(): void
    {
        $payload = ['submission_token' => (string) Str::uuid(), 'name' => 'Ankur', 'email' => 'ANKUR@example.com', 'subject' => 'Course details', 'message' => 'Please share the complete course details.'];
        $this->postJson('/api/v1/contact', $payload)->assertCreated()->assertJsonPath('data.email', 'ankur@example.com');
        $this->postJson('/api/v1/contact', $payload)->assertOk();
        $this->assertDatabaseCount('enquiries', 1);
    }

    public function test_honeypot_submission_is_rejected(): void
    {
        $this->postJson('/api/v1/contact', ['submission_token' => (string) Str::uuid(), 'name' => 'Bot', 'email' => 'bot@example.com', 'subject' => 'Spam subject', 'message' => 'This is automated spam content.', 'website' => 'spam'])->assertUnprocessable();
    }

    public function test_support_ticket_is_created_with_private_attachment(): void
    {
        Storage::fake('local');
        $response = $this->post('/api/v1/support', ['submission_token' => (string) Str::uuid(), 'name' => 'Ankur', 'email' => 'ankur@example.com', 'category' => 'technical', 'subject' => 'Download help', 'description' => 'The protected download is not opening.', 'attachments' => [UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf')]], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonPath('data.status', 'open');
        $this->assertDatabaseCount('support_ticket_attachments', 1);
    }

    public function test_customer_cannot_read_another_customers_support_ticket(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ticket = SupportTicket::create(['ticket_number' => 'SUP-2026-000001', 'user_id' => $owner->id, 'name' => $owner->name, 'email' => $owner->email, 'category' => 'general', 'subject' => 'Private issue', 'description' => 'This ticket belongs to another customer.', 'submission_token' => (string) Str::uuid()]);

        $this->actingAs($other, 'web')->getJson("/api/v1/account/support-tickets/{$ticket->id}")->assertNotFound();
    }

    public function test_workshop_capacity_and_duplicate_registration_are_enforced(): void
    {
        $workshop = Workshop::create(['title' => 'Grid Workshop', 'slug' => 'grid-workshop', 'description' => 'Learn modern grids.', 'start_at' => now()->addDays(5), 'end_at' => now()->addDays(5)->addHours(2), 'registration_closes_at' => now()->addDays(4), 'capacity' => 1, 'status' => 'published', 'published_at' => now()]);
        $this->postJson("/api/v1/workshops/{$workshop->id}/registrations", ['submission_token' => (string) Str::uuid(), 'name' => 'First', 'email' => 'first@example.com'])->assertCreated();
        $this->postJson("/api/v1/workshops/{$workshop->id}/registrations", ['submission_token' => (string) Str::uuid(), 'name' => 'Second', 'email' => 'second@example.com'])->assertUnprocessable()->assertJsonPath('message', 'Workshop is full.');
    }

    public function test_closed_workshop_rejects_registration(): void
    {
        $workshop = Workshop::create(['title' => 'Past Workshop', 'slug' => 'past-workshop', 'description' => 'Closed.', 'start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHour(), 'registration_closes_at' => now()->subMinute(), 'status' => 'published', 'published_at' => now()]);
        $this->postJson("/api/v1/workshops/{$workshop->id}/registrations", ['submission_token' => (string) Str::uuid(), 'name' => 'Late', 'email' => 'late@example.com'])->assertUnprocessable();
    }

    public function test_career_application_requires_safe_resume_and_prevents_duplicates(): void
    {
        Storage::fake('local');
        $job = JobOpening::create(['title' => 'Editor', 'slug' => 'editor', 'department' => 'Content', 'location' => 'Delhi', 'employment_type' => 'full_time', 'summary' => 'Engineering editor', 'description' => 'Edit engineering content.', 'application_deadline' => today()->addWeek(), 'status' => 'published', 'published_at' => now()]);
        $payload = ['submission_token' => (string) Str::uuid(), 'name' => 'Ankur', 'email' => 'ankur@example.com', 'mobile' => '9999999999', 'experience' => 3, 'resume' => UploadedFile::fake()->create('resume.pdf', 30, 'application/pdf')];
        $this->post("/api/v1/careers/{$job->id}/applications", $payload, ['Accept' => 'application/json'])->assertCreated();
        $payload['submission_token'] = (string) Str::uuid();
        $payload['resume'] = UploadedFile::fake()->create('resume.pdf', 30, 'application/pdf');
        $this->post("/api/v1/careers/{$job->id}/applications", $payload, ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_expired_job_rejects_application(): void
    {
        Storage::fake('local');
        $job = JobOpening::create(['title' => 'Old Role', 'slug' => 'old-role', 'department' => 'Content', 'location' => 'Delhi', 'employment_type' => 'full_time', 'summary' => 'Expired role', 'description' => 'Expired.', 'application_deadline' => today()->subDay(), 'status' => 'published', 'published_at' => now()->subWeek()]);
        $this->post("/api/v1/careers/{$job->id}/applications", ['submission_token' => (string) Str::uuid(), 'name' => 'Ankur', 'email' => 'ankur@example.com', 'mobile' => '9999999999', 'experience' => 3, 'resume' => UploadedFile::fake()->create('resume.pdf', 30, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();
    }
}
