<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use illuminate\Database\Eloquent\Factories\HasFactory;

class Halaman extends Model
{
    use HasFactory;
    protected $table = 'halaman';
    protected $primaryKey = 'id_halaman';
    public $timestamps = true;

    protected $fillable = [
        'id_buku',
        'id_audio_latar',
        'nomor_halaman',
        'path_gambar',
        'panjang_halaman',
        'lebar_halaman',
        'narasi_indo',
        'narasi_sunda'
    ];

    public function buku()
    {
        return $this->belongsTo(Buku::class, 'id_buku', 'id_buku');
    }

    public function audioLatar()
    {
        return $this->belongsTo(AudioLatar::class, 'id_audio_latar', 'id_audio_latar');
    }

    public function areaInteraktif()
    {
        return $this->hasMany(AreaInteraktif::class, 'id_halaman', 'id_halaman');
    }
}
