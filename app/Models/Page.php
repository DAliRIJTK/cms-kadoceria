<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'book_id',
        'page_number',
        'image_url'
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function boundingBoxes()
    {
        return $this->hasMany(BoundingBox::class);
    }

    public function audios()
    {
        return $this->hasMany(Audio::class);
    }
}