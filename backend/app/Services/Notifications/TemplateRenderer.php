<?php

namespace App\Services\Notifications;

use App\Models\NotificationTemplate;
use Illuminate\Validation\ValidationException;

class TemplateRenderer
{
    public function validate(NotificationTemplate $template, string $subject, string $body): array
    {
        if (preg_match('/[\r\n]/', $subject)) {
            throw ValidationException::withMessages(['subject' => ['Subject must be one line.']]);
        }
        $allowed = $template->available_variables ?? [];
        $used = [];
        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', $subject.' '.$body, $matches);
        foreach (array_unique($matches[1]) as $variable) {
            if (! in_array($variable, $allowed, true)) {
                throw ValidationException::withMessages(['body' => ["Unknown template variable: {$variable}."]]);
            } $used[] = $variable;
        }

        return ['subject' => $subject, 'body' => $this->sanitize($body), 'variables' => $used];
    }

    public function render(NotificationTemplate $template, array $context): array
    {
        $validated = $this->validate($template, $template->subject, $template->body);
        $replace = [];
        foreach ($template->available_variables ?? [] as $variable) {
            $replace['{{'.$variable.'}}'] = e((string) ($context[$variable] ?? ''));
            $replace['{{ '.$variable.' }}'] = $replace['{{'.$variable.'}}'];
        }

        return ['subject' => strtr($validated['subject'], $replace), 'html' => strtr($validated['body'], $replace), 'text' => trim(html_entity_decode(strip_tags(strtr($validated['body'], $replace))))];
    }

    public function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|style)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\s(on\w+|style)\s*=\s*(["\']).*?\2/is', '', $html) ?? '';
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*(javascript:|data:).*?\2/is', '$1="#"', $html) ?? '';

        return strip_tags($html,'<p><br><strong><b><em><i><a><ul><ol><li><table><tbody><tr><td><th><h1><h2><h3><hr>');
    }
}
