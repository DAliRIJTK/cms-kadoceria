<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buku extends Model
{
    use HasFactory;

    protected $table = 'buku';
    protected $primaryKey = 'id_buku';

    protected $fillable = [
        'id_pengelola',
        'judul_idn',
        'judul_sn',
        'nama_folder',
        'penulis',
        'ilustrator',
        'path_cover',
        'status_publikasi',
        'deskripsi_idn',
        'deskripsi_sn',
        'warna_primer',
        'warna_sekunder',
    ];

    public function halaman(): HasMany
    {
        return $this->hasMany(Halaman::class, 'id_buku', 'id_buku');
    }
}