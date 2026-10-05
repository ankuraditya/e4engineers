<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\CareerApplication;
use App\Models\Enquiry;
use App\Models\EnquiryNote;
use App\Models\JobOpening;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\Workshop;
use App\Services\OperationalNotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationsController extends Controller
{
    use ApiResponse;

    public function __construct(private OperationalNotificationService $notifications) {}

    private function listing(Request $r, string $model, array $search): JsonResponse
    {
        $q = $model::query();
        if ($r->filled('status')) {
            $q->where('status', $r->input('status'));
        } if ($r->filled('search')) {
            $q->where(function ($x) use ($r, $search) {
                foreach ($search as $i => $column) {
                    $method = $i ? 'orWhere' : 'where';
                    $x->{$method}($column, 'like', '%'.$r->input('search').'%');
                }
            });
        }

        return $this->successResponse($q->latest()->paginate(min((int) $r->input('per_page', 20), 100)));
    }

    public function enquiries(Request $r): JsonResponse
    {
        return $this->listing($r, Enquiry::class, ['reference_number', 'name', 'email', 'subject']);
    }

    public function enquiry(Enquiry $enquiry): JsonResponse
    {
        return $this->successResponse($enquiry->load('notes'));
    }

    public function updateEnquiry(Request $r, Enquiry $enquiry): JsonResponse
    {
        $enquiry->update($r->validate(['status' => 'sometimes|in:new,in_progress,resolved,closed', 'priority' => 'sometimes|in:low,normal,high,urgent', 'assigned_to' => 'nullable|exists:users,id']));

        return $this->successResponse($enquiry->refresh(), 'Enquiry updated.');
    }

    public function note(Request $r, Enquiry $enquiry): JsonResponse
    {
        $n = EnquiryNote::create(['enquiry_id' => $enquiry->id, 'admin_user_id' => $r->user()->id] + $r->validate(['note' => 'required|string|max:5000']));

        return $this->successResponse($n, 'Note added.', 201);
    }

    public function tickets(Request $r): JsonResponse
    {
        return $this->listing($r, SupportTicket::class, ['ticket_number', 'name', 'email', 'subject']);
    }

    public function ticket(SupportTicket $ticket): JsonResponse
    {
        return $this->successResponse($ticket->load(['messages', 'attachments']));
    }

    public function updateTicket(Request $r, SupportTicket $ticket): JsonResponse
    {
        $d = $r->validate(['status' => 'sometimes|in:open,in_progress,awaiting_customer,customer_reply,resolved,closed', 'priority' => 'sometimes|in:low,normal,high,urgent', 'assigned_to' => 'nullable|exists:users,id']);
        if (($d['status'] ?? null) === 'resolved') {
            $d['resolved_at'] = now();
        } if (($d['status'] ?? null) === 'closed') {
            $d['closed_at'] = now();
        } $ticket->update($d + ['last_activity_at' => now()]);
        if (in_array($d['status'] ?? '', ['resolved', 'closed'], true)) {
            $this->notifications->customer(NotificationType::SupportTicketResolved, $ticket, $ticket->email, ['name' => $ticket->name, 'ticket_number' => $ticket->ticket_number, 'subject' => $ticket->subject]);
        }

        return $this->successResponse($ticket->refresh(), 'Ticket updated.');
    }

    public function replyTicket(Request $r, SupportTicket $ticket): JsonResponse
    {
        $d = $r->validate(['message' => 'required|string|max:10000', 'is_internal' => 'nullable|boolean']);
        $m = SupportTicketMessage::create(['support_ticket_id' => $ticket->id, 'user_id' => $r->user()->id, 'sender_type' => 'admin', 'message' => $d['message'], 'is_internal' => $d['is_internal'] ?? false]);
        $ticket->update(['status' => ($d['is_internal'] ?? false) ? $ticket->status : 'awaiting_customer', 'last_activity_at' => now()]);
        if (! ($d['is_internal'] ?? false)) {
            $this->notifications->customer(NotificationType::SupportTicketReply, $ticket, $ticket->email, ['name' => $ticket->name, 'ticket_number' => $ticket->ticket_number, 'subject' => $ticket->subject]);
        }

        return $this->successResponse($m, 'Reply added.', 201);
    }

    public function supportAttachment(SupportTicketAttachment $attachment): StreamedResponse
    {
        return Storage::download($attachment->getRawOriginal('path'), $attachment->original_name);
    }

    public function workshops(Request $r): JsonResponse
    {
        return $this->listing($r, Workshop::class, ['title', 'slug']);
    }

    public function saveWorkshop(Request $r, ?Workshop $workshop = null): JsonResponse
    {
        $d = $r->validate(['title' => 'required|string|max:200', 'slug' => 'nullable|string|max:220', 'type' => 'required|in:workshop,webinar,seminar,training', 'short_description' => 'nullable|string|max:1000', 'description' => 'required|string', 'start_at' => 'required|date', 'end_at' => 'required|date|after:start_at', 'mode' => 'required|in:online,offline,hybrid', 'venue' => 'nullable|string|max:255', 'meeting_url' => 'nullable|url|max:1000', 'registration_opens_at' => 'nullable|date', 'registration_closes_at' => 'nullable|date|before_or_equal:start_at', 'capacity' => 'nullable|integer|min:1', 'registration_type' => 'required|in:free,paid', 'fee' => 'nullable|numeric|min:0', 'currency' => 'nullable|string|size:3', 'status' => 'required|in:draft,published,cancelled,completed', 'is_featured' => 'nullable|boolean']);
        $d['slug'] = $d['slug'] ?? Str::slug($d['title']);
        if ($d['status'] === 'published') {
            $d['published_at'] = $workshop?->published_at ?? now();
        } if ($workshop) {
            $workshop->update($d);
            $m = $workshop->refresh();
        } else {
            $m = Workshop::create($d + ['created_by' => $r->user()->id]);
        }

        return $this->successResponse($m, 'Workshop saved.', $workshop ? 200 : 201);
    }

    public function deleteWorkshop(Workshop $workshop): JsonResponse
    {
        abort_if($workshop->registrations()->exists(), 422, 'A workshop with registrations cannot be deleted.');
        $workshop->delete();

        return $this->successResponse(null, 'Workshop deleted.');
    }

    public function registrations(Workshop $workshop): JsonResponse
    {
        return $this->successResponse($workshop->registrations()->latest()->paginate(100));
    }

    public function jobs(Request $r): JsonResponse
    {
        return $this->listing($r, JobOpening::class, ['title', 'department', 'location']);
    }

    public function saveJob(Request $r, ?JobOpening $job = null): JsonResponse
    {
        $d = $r->validate(['title' => 'required|string|max:200', 'slug' => 'nullable|string|max:220', 'department' => 'required|string|max:120', 'location' => 'required|string|max:120', 'employment_type' => 'required|in:full_time,part_time,contract,internship', 'work_mode' => 'required|in:onsite,remote,hybrid', 'summary' => 'required|string|max:1500', 'description' => 'required|string', 'responsibilities' => 'nullable|string', 'requirements' => 'nullable|string', 'preferred_skills' => 'nullable|string', 'application_deadline' => 'nullable|date', 'status' => 'required|in:draft,published,closed', 'is_featured' => 'nullable|boolean']);
        $d['slug'] = $d['slug'] ?? Str::slug($d['title']);
        if ($d['status'] === 'published') {
            $d['published_at'] = $job?->published_at ?? now();
        } if ($job) {
            $job->update($d);
            $m = $job->refresh();
        } else {
            $m = JobOpening::create($d + ['created_by' => $r->user()->id]);
        }

        return $this->successResponse($m, 'Job opening saved.', $job ? 200 : 201);
    }

    public function deleteJob(JobOpening $job): JsonResponse
    {
        abort_if($job->applications()->exists(), 422, 'A job opening with applications cannot be deleted. Close it instead.');
        $job->delete();

        return $this->successResponse(null, 'Job opening deleted.');
    }

    public function applications(Request $r): JsonResponse
    {
        return $this->listing($r, CareerApplication::class, ['reference_number', 'name', 'email']);
    }

    public function updateApplication(Request $r, CareerApplication $application): JsonResponse
    {
        $application->update($r->validate(['status' => 'required|in:received,reviewing,shortlisted,rejected,hired', 'assigned_to' => 'nullable|exists:users,id']));

        return $this->successResponse($application->refresh(), 'Application updated.');
    }

    public function resume(CareerApplication $application): StreamedResponse
    {
        return Storage::download($application->getRawOriginal('resume_path'), $application->resume_original_name);
    }
}
