<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiPromptRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'instructions',
    ];

    /**
     * Retrieve the active instructions for tag generation.
     */
    public static function currentInstructions(): string
    {
        return Cache::rememberForever('ai_prompt_rules.current_instructions', function (): string {
            $record = static::query()
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            return $record?->instructions ?? '';
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('ai_prompt_rules.current_instructions');
    }
}


