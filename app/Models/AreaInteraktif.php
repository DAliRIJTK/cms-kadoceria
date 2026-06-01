<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaInteraktif extends Model
{
    protected $table = 'area_interaktif';
    protected $primaryKey = 'id_area';
    public $timestamps = true;

    protected $fillable = [
        'id_halaman',
        'x',
        'y',
        'lebar_area',
        'panjang_area',
        'audio_indo',
        'audio_sunda'
    ];

    public function halaman()
    {
        return $this->belongsTo(Halaman::class, 'id_halaman', 'id_halaman');
    }
}
