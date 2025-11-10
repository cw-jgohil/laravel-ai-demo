<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Protocol extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    /**
     * Relation to Tag models (avoid name collision with JSON 'tags' column).
     */
    public function tagsRelation()
    {
        return $this->belongsToMany(Tag::class, 'protocol_tag')->withTimestamps();
    }
}


