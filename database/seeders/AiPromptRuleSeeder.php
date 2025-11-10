<?php

namespace Database\Seeders;

use App\Models\AiPromptRule;
use Illuminate\Database\Seeder;

class AiPromptRuleSeeder extends Seeder
{
    public function run(): void
    {
        AiPromptRule::query()->delete();

        AiPromptRule::create([
            'name' => 'Default',
            'instructions' => <<<RULES
Return tags that are lower-case, concise, and clinically relevant.
Include both common abbreviations and their expanded full form when useful.
Split combined abbreviations such as "vf/vt" into separate tags (e.g. "vf", "vt").
Avoid duplicates and keep the list focused on search-friendly terminology.
RULES,
        ]);

        AiPromptRule::forgetCache();
    }
}


