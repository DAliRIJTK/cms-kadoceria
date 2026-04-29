<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoundingBox extends Model
{
    protected $fillable = [
        'page_id',
        'x',
        'y',
        'width',
        'height',
        'label'
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function audios()
    {
        return $this->hasMany(Audio::class);
    }
}
