<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'publisher',
        'description',
        'cover_image',
        'status'
    ];

    public function pages()
    {
        return $this->hasMany(Page::class);
    }
}