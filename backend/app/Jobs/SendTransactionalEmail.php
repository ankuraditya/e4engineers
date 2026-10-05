<?php

namespace App\Jobs;

use App\Mail\TransactionalEmail;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\Notifications\DynamicMailConfigurator;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTransactionalEmail implements ShouldQueue
{
    use FoundationQueueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public string $logId) {}

    public function handle(DynamicMailConfigurator $mail, TemplateRenderer $renderer): void
    {
        $log = NotificationLog::findOrFail($this->logId);
        if ($log->status === 'sent') {
            return;
        } $log->increment('attempt_count');
        $settings = $mail->apply();
        $template = NotificationTemplate::findOrFail($log->template_id);
        $rendered = $renderer->render($template, $log->context ?? []);
        Mail::mailer('dynamic')->to($log->recipient)->send(new TransactionalEmail($rendered['subject'], $rendered['html'], $rendered['text'], $settings->reply_to_email));
        $log->update(['subject' => $rendered['subject'], 'status' => 'sent', 'sent_at' => now(), 'failed_at' => null, 'failure_code' => null, 'failure_message' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        NotificationLog::whereKey($this->logId)->update(['status' => 'failed', 'failed_at' => now(), 'failure_code' => 'MAIL_TRANSPORT_FAILED', 'failure_message' => 'Email delivery failed.']);
    }
}
