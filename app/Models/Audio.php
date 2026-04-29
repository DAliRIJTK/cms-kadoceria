<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audio extends Model
{
    protected $table = 'audios';
    protected $fillable = [
        'page_id',
        'bounding_box_id',
        'type',
        'label',
        'file_url',
        'duration'
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function boundingBox()
    {
        return $this->belongsTo(BoundingBox::class);
    }
}