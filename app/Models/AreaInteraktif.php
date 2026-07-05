<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use illuminate\Database\Eloquent\Factories\HasFactory;

class AreaInteraktif extends Model
{
    use HasFactory;
    protected $table = 'area_interaktif';
    protected $primaryKey = 'id_area';
    public $timestamps = true;

    protected $fillable = [
        'id_halaman',
        'label',
        'x',
        'y',
        'lebar_area',
        'panjang_area',
        'x_pct',
        'y_pct',
        'w_pct',
        'h_pct',
        'audio_indo',
        'audio_sunda',
    ];

    protected $casts = [
        'x_pct' => 'float',
        'y_pct' => 'float',
        'w_pct' => 'float',
        'h_pct' => 'float',
    ];

    public function halaman()
    {
        return $this->belongsTo(Halaman::class, 'id_halaman', 'id_halaman');
    }
}