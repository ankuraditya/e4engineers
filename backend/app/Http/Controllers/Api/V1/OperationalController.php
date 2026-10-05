<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\CareerApplication;
use App\Models\Enquiry;
use App\Models\JobOpening;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Services\OperationalNotificationService;
use App\Support\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OperationalController extends Controller
{
    use ApiResponse;

    public function __construct(private OperationalNotificationService $notifications) {}

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate(['website' => 'nullable|max:0', 'submission_token' => 'required|uuid', 'type' => 'nullable|in:general,partnership,content,technical,billing,feedback', 'name' => 'required|string|max:120', 'email' => 'required|email|max:255', 'mobile' => 'nullable|string|max:30', 'subject' => 'required|string|max:200', 'message' => 'required|string|min:10|max:5000']);
        unset($data['website']);
        if ($existing = Enquiry::where('submission_token', $data['submission_token'])->first()) {
            return $this->successResponse($existing, 'Enquiry already received.');
        }
        $duplicate = Enquiry::where('email', mb_strtolower($data['email']))->where('subject', $data['subject'])->where('message', $data['message'])->where('created_at', '>=', now()->subMinutes(10))->first();
        if ($duplicate) {
            return $this->successResponse($duplicate, 'Enquiry already received.');
        }
        $data['email'] = mb_strtolower($data['email']);
        $data['user_id'] = $request->user()?->id;
        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();
        $enquiry = DB::transaction(function () use ($data) {
            $m = Enquiry::create($data);
            $m->update(['reference_number' => 'ENQ-'.now()->format('Y').'-'.str_pad((string) $m->id, 6, '0', STR_PAD_LEFT)]);

            return $m;
        });
        $ctx = ['name' => $enquiry->name, 'reference_number' => $enquiry->reference_number, 'subject' => $enquiry->subject];
        $this->notifications->customer(NotificationType::ContactEnquiryReceived, $enquiry, $enquiry->email, $ctx);
        $this->notifications->admin(NotificationType::ContactAdminAlert, $enquiry, $ctx);

        return $this->successResponse($enquiry, 'Thank you. Your enquiry has been received.', 201);
    }

    public function support(Request $request): JsonResponse
    {
        $data = $request->validate(['website' => 'nullable|max:0', 'submission_token' => 'required|uuid', 'name' => 'required|string|max:120', 'email' => 'required|email|max:255', 'mobile' => 'nullable|string|max:30', 'category' => 'required|in:general,order,payment,shipping,digital_access,technical', 'subject' => 'required|string|max:200', 'description' => 'required|string|min:10|max:10000', 'order_number' => 'nullable|string|max:100', 'attachments' => 'nullable|array|max:3', 'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120']);
        unset($data['website'],$data['attachments']);
        if ($existing = SupportTicket::where('submission_token', $data['submission_token'])->first()) {
            return $this->successResponse($existing, 'Support request already received.');
        }
        $order = null;
        if (! empty($data['order_number'])) {
            abort_unless($request->user(), 403, 'Sign in to link an order.');
            $order = Order::where('order_number', $data['order_number'])->where('user_id', $request->user()->id)->firstOrFail();
        } unset($data['order_number']);
        $data['email'] = mb_strtolower($data['email']);
        $data['user_id'] = $request->user()?->id;
        $data['order_id'] = $order?->id;
        $data['last_activity_at'] = now();
        $ticket = DB::transaction(function () use ($data, $request) {
            $t = SupportTicket::create($data);
            $t->update(['ticket_number' => 'SUP-'.now()->format('Y').'-'.str_pad((string) $t->id, 6, '0', STR_PAD_LEFT)]);
            $msg = SupportTicketMessage::create(['support_ticket_id' => $t->id, 'user_id' => $t->user_id, 'sender_type' => 'customer', 'message' => $t->description]);
            foreach ($request->file('attachments', []) as $file) {
                SupportTicketAttachment::create(['support_ticket_id' => $t->id, 'support_ticket_message_id' => $msg->id, 'path' => $file->store('support-attachments'), 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
            }

return $t;
        });
        $ctx = ['name' => $ticket->name, 'ticket_number' => $ticket->ticket_number, 'subject' => $ticket->subject];
        $this->notifications->customer(NotificationType::SupportTicketCreated, $ticket, $ticket->email, $ctx);
        $this->notifications->admin(NotificationType::SupportAdminAlert, $ticket, $ctx);

        return $this->successResponse($ticket->refresh(), 'Your support ticket has been created.', 201);
    }

    public function workshops(Request $request): JsonResponse
    {
        $items = Workshop::where('status', 'published')->where('published_at', '<=', now())->withCount('registrations')->orderBy('start_at')->paginate(min((int) $request->input('per_page', 12), 50));

        return $this->successResponse($items->items(), 'Workshops retrieved.', 200, ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]);
    }

    public function workshop(string $slug): JsonResponse
    {
        $item = Workshop::where('slug', $slug)->where('status', 'published')->where('published_at', '<=', now())->withCount('registrations')->firstOrFail();

        return $this->successResponse($item);
    }

    public function registerWorkshop(Request $request, Workshop $workshop): JsonResponse
    {
        $data = $request->validate(['website' => 'nullable|max:0', 'submission_token' => 'required|uuid', 'name' => 'required|string|max:120', 'email' => 'required|email|max:255', 'mobile' => 'nullable|string|max:30', 'notes' => 'nullable|string|max:2000']);
        unset($data['website']);
        $data['email'] = mb_strtolower($data['email']);
        if ($existing = WorkshopRegistration::where('submission_token', $data['submission_token'])->first()) {
            return $this->successResponse($existing, 'Registration already received.');
        }
        try {
            $registration = DB::transaction(function () use ($data, $request, $workshop) {
                $w = Workshop::lockForUpdate()->findOrFail($workshop->id);
                abort_unless($w->status === 'published' && (! $w->registration_opens_at || $w->registration_opens_at <= now()) && (! $w->registration_closes_at || $w->registration_closes_at >= now()), 422, 'Registration is closed.');
                if ($w->capacity !== null && $w->registrations()->count() >= $w->capacity) {
                    abort(422, 'Workshop is full.');
                }

return $w->registrations()->create($data + ['user_id' => $request->user()?->id, 'registered_at' => now()]);
            });
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23000', '19'], true)) {
                return $this->errorResponse('You are already registered for this workshop.', [], 422);
            } throw $e;
        }
        $ctx = ['name' => $registration->name, 'workshop_title' => $workshop->title];
        $this->notifications->customer(NotificationType::WorkshopRegistrationReceived, $registration, $registration->email, $ctx);
        $this->notifications->admin(NotificationType::WorkshopAdminAlert, $registration, $ctx);

        return $this->successResponse($registration, 'Workshop registration received.', 201);
    }

    public function careers(Request $request): JsonResponse
    {
        $q = JobOpening::where('status', 'published')->where('published_at', '<=', now())->where(fn ($x) => $x->whereNull('application_deadline')->orWhere('application_deadline', '>=', today()));
        if ($request->filled('department')) {
            $q->where('department', $request->input('department'));
        }

return $this->successResponse($q->orderByDesc('is_featured')->latest()->get(), 'Open positions retrieved.');
    }

    public function career(string $slug): JsonResponse
    {
        return $this->successResponse(JobOpening::where('slug', $slug)->where('status', 'published')->where('published_at', '<=', now())->firstOrFail());
    }

    public function apply(Request $request, JobOpening $job): JsonResponse
    {
        $data = $request->validate(['website' => 'nullable|max:0', 'submission_token' => 'required|uuid', 'name' => 'required|string|max:120', 'email' => 'required|email|max:255', 'mobile' => 'required|string|max:30', 'current_location' => 'nullable|string|max:120', 'experience' => 'required|integer|min:0|max:60', 'portfolio_url' => 'nullable|url|max:500', 'cover_letter' => 'nullable|string|max:5000', 'resume' => 'required|file|mimes:pdf,doc,docx|max:5120']);
        unset($data['website'],$data['resume']);
        $data['email'] = mb_strtolower($data['email']);
        abort_unless($job->status === 'published' && $job->published_at <= now() && (! $job->application_deadline || $job->application_deadline >= today()), 422, 'This position is not accepting applications.');
        if ($existing = CareerApplication::where('submission_token', $data['submission_token'])->first()) {
            return $this->successResponse($existing, 'Application already received.');
        }
        $file = $request->file('resume');
        $path = $file->store('career-resumes');
        try {
            $application = DB::transaction(function () use ($data, $request, $job, $file, $path) {
                $a = $job->applications()->create($data + ['user_id' => $request->user()?->id, 'resume_path' => $path, 'resume_original_name' => $file->getClientOriginalName(), 'resume_mime_type' => $file->getMimeType(), 'resume_size' => $file->getSize(), 'applied_at' => now()]);
                $a->update(['reference_number' => 'JOB-'.now()->format('Y').'-'.str_pad((string) $a->id, 6, '0', STR_PAD_LEFT)]);

                return $a;
            });
        } catch (QueryException $e) {
            Storage::delete($path);
            if (in_array($e->getCode(), ['23000', '19'], true)) {
                return $this->errorResponse('You have already applied for this position.', [], 422);
            } throw $e;
        }
        $ctx = ['name' => $application->name, 'job_title' => $job->title, 'reference_number' => $application->reference_number];
        $this->notifications->customer(NotificationType::CareerApplicationReceived,$application,$application->email,$ctx);
        $this->notifications->admin(NotificationType::CareerAdminAlert,$application,$ctx);

        return $this->successResponse($application,'Application received.',201);
    }
}
