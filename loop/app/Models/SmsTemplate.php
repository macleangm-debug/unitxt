<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'name',
    'audience',
    'body_en',
    'body_sw',
    'is_active',
])]
class SmsTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function body(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        if ($locale === 'sw' && filled($this->body_sw)) {
            return (string) $this->body_sw;
        }

        return (string) $this->body_en;
    }
}
