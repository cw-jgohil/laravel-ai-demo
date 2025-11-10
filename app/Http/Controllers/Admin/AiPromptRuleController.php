<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiPromptRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiPromptRuleController extends Controller
{
    public function edit(): View
    {
        $rule = AiPromptRule::query()->orderByDesc('updated_at')->first();

        return view('admin.ai-rules.edit', [
            'rule' => $rule,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
        ]);

        $rule = AiPromptRule::query()->orderByDesc('updated_at')->first();

        if ($rule === null) {
            AiPromptRule::create($data);
        } else {
            $rule->update($data);
        }

        AiPromptRule::forgetCache();

        return redirect()
            ->route('admin.ai-rules.edit')
            ->with('status', 'AI prompt rules updated.');
    }
}


