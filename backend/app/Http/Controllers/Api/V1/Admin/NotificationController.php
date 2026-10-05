<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TransactionalEmail;
use App\Models\EmailSetting;
use App\Models\NotificationAuditLog;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\Notifications\DynamicMailConfigurator;
use App\Services\Notifications\NotificationManager;
use App\Services\Notifications\TemplateRenderer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    use ApiResponse;

    public function settings(): JsonResponse
    {
        return $this->successResponse(EmailSetting::current()->safe());
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate(['is_enabled' => 'sometimes|boolean', 'mailer' => 'sometimes|in:smtp', 'host' => 'sometimes|nullable|string|max:255', 'port' => 'sometimes|integer|between:1,65535', 'username' => 'sometimes|nullable|string|max:255', 'password' => 'sometimes|nullable|string|max:500', 'encryption' => 'sometimes|nullable|in:tls,ssl,none', 'from_email' => 'sometimes|nullable|email:rfc', 'from_name' => 'sometimes|string|max:150', 'reply_to_email' => 'sometimes|nullable|email:rfc', 'queue_enabled' => 'sometimes|boolean']);
        $settings = EmailSetting::current();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } $passwordChanged = array_key_exists('password', $data);
        $settings->update($data + ['connection_status' => 'not_tested', 'last_error' => null]);
        $this->audit($request, $passwordChanged ? 'smtp_password_changed' : 'smtp_settings_changed', ['fields' => array_values(array_diff(array_keys($data), ['password']))]);

        return $this->successResponse($settings->refresh()->safe(), 'Email settings saved.');
    }

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate(['enabled' => 'required|boolean']);
        $settings = EmailSetting::current();
        $settings->update(['is_enabled' => $data['enabled']]);
        $this->audit($request, $data['enabled'] ? 'email_enabled' : 'email_disabled');

        return $this->successResponse($settings->safe());
    }

    public function testConnection(Request $request, DynamicMailConfigurator $configurator): JsonResponse
    {
        $settings = EmailSetting::current();
        try {
            $configurator->test($settings);
            $settings->update(['connection_status' => 'connected', 'last_tested_at' => now(), 'last_error' => null]);
            $this->audit($request, 'smtp_connection_test', ['result' => 'connected']);

            return $this->successResponse($settings->safe(), 'SMTP connection successful.');
        } catch (\Throwable) {
            $settings->update(['connection_status' => 'error', 'last_tested_at' => now(), 'last_error' => 'Unable to authenticate with the SMTP server.']);
            $this->audit($request, 'smtp_connection_test', ['result' => 'error']);

            return $this->errorResponse('SMTP connection failed. Check the saved server settings.', status: 422);
        }
    }

    public function sendTest(Request $request, DynamicMailConfigurator $configurator): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email:rfc']);
        $settings = $configurator->apply();
        Mail::mailer('dynamic')->to($data['email'])->send(new TransactionalEmail('E4ENGINEERS Email Configuration Test', '<h2>Email configuration test</h2><p>Your E4ENGINEERS email configuration is working correctly.</p>', 'Your E4ENGINEERS email configuration is working correctly.', $settings->reply_to_email));
        $this->audit($request, 'test_email_sent', ['recipient' => $data['email']]);

        return $this->successResponse(null, 'Test email sent successfully.');
    }

    public function templates(): JsonResponse
    {
        return $this->successResponse(NotificationTemplate::orderBy('name')->get());
    }

    public function updateTemplate(Request $request, NotificationTemplate $template, TemplateRenderer $renderer): JsonResponse
    {
        $data = $request->validate(['name' => 'sometimes|string|max:150', 'subject' => 'sometimes|string|max:255', 'body' => 'sometimes|string|max:50000', 'is_enabled' => 'sometimes|boolean']);
        $validated = $renderer->validate($template, $data['subject'] ?? $template->subject, $data['body'] ?? $template->body);
        $template->update(array_merge($data, ['subject' => $validated['subject'], 'body' => $validated['body'], 'updated_by' => $request->user()->id]));
        $this->audit($request, 'notification_template_updated', ['template_id' => $template->id]);

        return $this->successResponse($template->refresh());
    }

    public function preview(Request $request, NotificationTemplate $template, TemplateRenderer $renderer): JsonResponse
    {
        $data = $request->validate(['context' => 'sometimes|array']);

        return $this->successResponse($renderer->render($template, $data['context'] ?? array_fill_keys($template->available_variables, 'Example')));
    }

    public function logs(Request $request): JsonResponse
    {
        $items = NotificationLog::with('template:id,name')->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))->when($request->filled('recipient'), fn ($q) => $q->where('recipient', 'like', '%'.$request->string('recipient').'%'))->latest()->paginate(30);

        return $this->successResponse($items->items(), meta: ['pagination' => ['total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }

    public function log(NotificationLog $notificationLog): JsonResponse
    {
        return $this->successResponse($notificationLog->makeVisible('context')->load('template'));
    }

    public function retry(NotificationLog $notificationLog, NotificationManager $manager): JsonResponse
    {
        return $this->successResponse($manager->retry($notificationLog), 'Notification queued for retry.');
    }

    private function audit(Request $request, string $event, array $context = []): void
    {
        NotificationAuditLog::create(['user_id' => $request->user()?->id, 'event' => $event, 'context' => $context]);
    }
}
