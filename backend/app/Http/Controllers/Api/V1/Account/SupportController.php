<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Services\OperationalNotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportController extends Controller
{
    use ApiResponse;

    public function __construct(private OperationalNotificationService $notifications) {}

    public function index(Request $r): JsonResponse
    {
        return $this->successResponse(SupportTicket::where('user_id', $r->user()->id)->latest('last_activity_at')->paginate(20));
    }

    public function show(Request $r, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $r->user()->id, 404);

        return $this->successResponse($ticket->load(['messages' => fn ($q) => $q->where('is_internal', false), 'attachments']));
    }

    public function reply(Request $r, SupportTicket $ticket): JsonResponse
    {
        abort_unless($ticket->user_id === $r->user()->id, 404);
        abort_if(in_array($ticket->status, ['closed'], true), 422, 'Ticket is closed.');
        $data = $r->validate(['message' => 'required|string|max:10000', 'attachments' => 'nullable|array|max:3', 'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120']);
        $m = SupportTicketMessage::create(['support_ticket_id' => $ticket->id, 'user_id' => $r->user()->id, 'sender_type' => 'customer', 'message' => $data['message']]);
        foreach ($r->file('attachments', []) as $file) {
            SupportTicketAttachment::create(['support_ticket_id' => $ticket->id, 'support_ticket_message_id' => $m->id, 'path' => $file->store('support-attachments'), 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
        } $ticket->update(['status' => 'customer_reply', 'last_activity_at' => now()]);
        $this->notifications->admin(NotificationType::SupportAdminAlert, $ticket, ['name' => $ticket->name, 'ticket_number' => $ticket->ticket_number, 'subject' => $ticket->subject]);

        return $this->successResponse($m, 'Reply added.', 201);
    }

    public function attachment(Request $r, SupportTicketAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->supportTicket?->user_id === $r->user()->id, 404);

        return Storage::download($attachment->getRawOriginal('path'), $attachment->original_name);
    }
}
