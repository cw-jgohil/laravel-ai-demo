<?php

namespace Database\Seeders;

use App\Models\Protocol;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagsBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $protocols = Protocol::query()->get();
        foreach ($protocols as $protocol) {
            $tagLabels = is_array($protocol->tags) ? $protocol->tags : [];
            if (empty($tagLabels)) {
                continue;
            }
            $tagIds = [];
            foreach ($tagLabels as $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }
                $key = $this->slugifyKey($label);
                $tag = Tag::query()->firstOrCreate(
                    ['key' => $key],
                    ['label' => $label]
                );
                $tagIds[] = $tag->id;
            }
            if (!empty($tagIds) && method_exists($protocol, 'tagsRelation')) {
                $protocol->tagsRelation()->syncWithoutDetaching($tagIds);
            }
        }
    }

    private function slugifyKey(string $label): string
    {
        $k = mb_strtolower($label);
        $k = preg_replace('/[^a-z0-9]+/i', '-', $k) ?? '';
        $k = trim($k, '-');
        return $k !== '' ? $k : substr(md5($label), 0, 8);
    }
}


