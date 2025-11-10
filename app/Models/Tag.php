<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
    ];

    public function protocols()
    {
        return $this->belongsToMany(Protocol::class, 'protocol_tag')->withTimestamps();
    }
}

