<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Halaman extends Model
{
    use HasFactory;

    protected $table = 'halaman';
    protected $primaryKey = 'id_halaman';

    protected $fillable = [
        'id_buku',
        'id_audio_latar',
        'nomor_halaman',
        'path_gambar',
        'panjang_halaman',
        'lebar_halaman',
        'narasi_indo',
        'narasi_sunda',
    ];

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'id_buku', 'id_buku');
    }

    public function audioLatar(): BelongsTo
    {
        return $this->belongsTo(AudioLatar::class, 'id_audio_latar', 'id_audio_latar');
    }
    public function areaInteraktif(): HasMany
    {
        return $this->hasMany(AreaInteraktif::class, 'id_halaman', 'id_halaman');
    }

    public function isMultimediaComplete()
    {
        return !empty($this->narasi_indo) && 
            !empty($this->narasi_sunda) && 
            !is_null($this->id_audio_latar);
    }
}