<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaInteraktif extends Model
{
    use HasFactory;

    protected $table = 'area_interaktif';
    protected $primaryKey = 'id_area';

    protected $fillable = [
        'id_halaman',
        'x',
        'y',
        'lebar_area',
        'panjang_area',
        'audio_indo',
        'audio_sunda',
    ];

    public function halaman(): BelongsTo
    {
        return $this->belongsTo(Halaman::class, 'id_halaman', 'id_halaman');
    }
}