<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $guarded = [];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'queue_enabled' => 'boolean', 'password' => 'encrypted', 'last_tested_at' => 'datetime'];
    }

    public static function current(): self
    {
        $model = static::firstOrCreate([], []);

        return $model->wasRecentlyCreated ? $model->refresh() : $model;
    }

    public function safe(): array
    {
        return $this->only(['is_enabled', 'mailer', 'host', 'port', 'username', 'encryption', 'from_email', 'from_name', 'reply_to_email', 'queue_enabled', 'connection_status', 'last_tested_at', 'last_error']) + ['password_configured' => filled($this->password)];
    }
}
