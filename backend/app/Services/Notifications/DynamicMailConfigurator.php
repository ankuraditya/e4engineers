<?php

namespace App\Services\Notifications;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class DynamicMailConfigurator
{
    public function apply(?EmailSetting $settings = null): EmailSetting
    {
        $settings ??= EmailSetting::current();
        if (! $settings->is_enabled || ! $settings->host || ! $settings->from_email) {
            throw new \RuntimeException('Email is disabled or SMTP settings are incomplete.');
        }
        config(['mail.mailers.dynamic' => ['transport' => 'smtp', 'scheme' => $settings->encryption === 'ssl' ? 'smtps' : null, 'host' => $settings->host, 'port' => $settings->port, 'username' => $settings->username, 'password' => $settings->password, 'timeout' => 15], 'mail.from' => ['address' => $settings->from_email, 'name' => $settings->from_name]]);
        Mail::purge('dynamic');

        return $settings;
    }

    public function test(EmailSetting $settings): void
    {
        $tls = $settings->encryption !== 'none';
        $transport = new EsmtpTransport((string) $settings->host, (int) $settings->port, $tls);
        if ($settings->username) {
            $transport->setUsername($settings->username);
        } if ($settings->password) {
            $transport->setPassword($settings->password);
        } $transport->start();
        $transport->stop();
    }
}
