<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $tags = Tag::query()
            ->when($q !== '', fn ($qB) => $qB->where('label', 'like', "%{$q}%")->orWhere('key', 'like', "%{$q}%"))
            ->orderBy('label')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tags.index', compact('tags', 'q'));
    }

    public function create(): View
    {
        $tag = new Tag();
        return view('admin.tags.create', compact('tag'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:tags,key'],
            'label' => ['required', 'string', 'max:255'],
        ]);
        Tag::create($data);
        return redirect()->route('admin.tags.index')->with('status', 'Tag created.');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:tags,key,' . $tag->id],
            'label' => ['required', 'string', 'max:255'],
        ]);
        $tag->update($data);
        return redirect()->route('admin.tags.index')->with('status', 'Tag updated.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $label = $tag->label;
        $tag->delete();
        return redirect()->route('admin.tags.index')->with('status', "Tag \"{$label}\" deleted.");
    }

    public function suggest(Request $request): array
    {
        $q = trim((string) $request->get('q', ''));
        $items = Tag::query()
            ->when($q !== '', fn ($qB) => $qB->where('label', 'like', "%{$q}%")->orWhere('key', 'like', "%{$q}%"))
            ->orderBy('label')
            ->limit(20)
            ->get(['key', 'label'])
            ->all();
        return ['tags' => array_values($items)];
    }
}


