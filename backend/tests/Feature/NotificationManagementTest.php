<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Jobs\SendTransactionalEmail;
use App\Mail\TransactionalEmail;
use App\Models\EmailSetting;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Notifications\NotificationManager;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, NotificationTemplateSeeder::class]);
    }

    public function test_smtp_secret_is_encrypted_masked_and_blank_update_retains_it(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->putJson('/api/v1/admin/notifications/email/settings', ['host' => 'smtp.example.com', 'port' => 587, 'username' => 'mailer', 'password' => 'top-secret', 'encryption' => 'tls', 'from_email' => 'mail@example.com', 'from_name' => 'E4ENGINEERS'])->assertOk()->assertJsonPath('data.password_configured', true)->assertJsonMissing(['password' => 'top-secret']);
        $this->assertStringNotContainsString('top-secret', (string) \DB::table('email_settings')->value('password'));
        $this->putJson('/api/v1/admin/notifications/email/settings', ['host' => 'smtp2.example.com', 'password' => ''])->assertOk();
        $this->assertSame('top-secret', EmailSetting::current()->password);
    }

    public function test_notification_is_queued_once_and_disabled_email_is_skipped(): void
    {
        Queue::fake();
        $settings = EmailSetting::current();
        $settings->update(['is_enabled' => true, 'host' => 'smtp.example.com', 'from_email' => 'mail@example.com']);
        $manager = app(NotificationManager::class);
        $context = ['customer_name' => 'Engineer', 'order_number' => 'E4E-1', 'order_total' => 'INR 499', 'payment_method' => 'Cash on Delivery', 'order_url' => 'https://example.com/order'];
        $first = $manager->dispatch(NotificationType::OrderConfirmed, 'engineer@example.com', $context, 'order-confirmed:1');
        $second = $manager->dispatch(NotificationType::OrderConfirmed, 'engineer@example.com', $context, 'order-confirmed:1');
        $this->assertSame($first->id, $second->id);
        Queue::assertPushed(SendTransactionalEmail::class, 1);
        $settings->update(['is_enabled' => false]);
        $skipped = $manager->dispatch(NotificationType::OrderConfirmed, 'other@example.com', $context, 'order-confirmed:2');
        $this->assertSame('skipped', $skipped->status);
    }

    public function test_template_rejects_unknown_variables_and_sanitizes_active_content(): void
    {
        $template = NotificationTemplate::where('type', NotificationType::OrderConfirmed)->firstOrFail();
        $admin = $this->admin();
        $this->actingAs($admin)->putJson('/api/v1/admin/notifications/templates/'.$template->id, ['subject' => 'Hello {{unknown}}', 'body' => '<p>Safe</p>'])->assertUnprocessable();
        $this->putJson('/api/v1/admin/notifications/templates/'.$template->id, ['subject' => 'Order {{order_number}}', 'body' => '<script>alert(1)</script><p onclick="bad()"><a href="javascript:bad()">Order</a></p>'])->assertOk();
        $body = $template->fresh()->body;
        $this->assertStringNotContainsString('script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('javascript:', $body);
    }

    public function test_preferences_are_persisted_and_security_categories_remain_available(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/api/v1/account/notification-preferences', ['email_enabled' => false, 'shipping_updates' => false])->assertOk()->assertJsonPath('data.email_enabled', false);
        $this->assertFalse(NotificationPreference::where('user_id', $user->id)->firstOrFail()->shipping_updates);
    }

    public function test_authorized_admin_can_send_test_email_and_customer_cannot(): void
    {
        Mail::fake();
        EmailSetting::current()->update(['is_enabled' => true, 'host' => 'smtp.example.com', 'port' => 587, 'from_email' => 'mail@example.com', 'from_name' => 'E4ENGINEERS']);
        $this->actingAs($this->admin())->postJson('/api/v1/admin/notifications/email/send-test', ['email' => 'qa@example.com'])->assertOk();
        Mail::assertSent(TransactionalEmail::class);
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($customer)->postJson('/api/v1/admin/notifications/email/send-test', ['email' => 'qa@example.com'])->assertForbidden();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        return $user;
    }
}
