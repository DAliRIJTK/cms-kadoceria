<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buku extends Model
{
    protected $table = 'buku';
    protected $primaryKey = 'id_buku';
    public $timestamps = true;

    protected $fillable = [
        'id_pengelola',
        'judul_idn',
        'judul_sn',
        'penulis',
        'ilustrator',
        'path_cover',
        'original_pdf_name',
        'status_publikasi',
        'deskripsi_idn',
        'deskripsi_sn',
        'warna_primer',
        'warna_sekunder'
    ];

    public function pengelola()
    {
        return $this->belongsTo(User::class, 'id_pengelola', 'id');
    }

    public function halaman()
    {
        return $this->hasMany(Halaman::class, 'id_buku', 'id_buku');
    }
}
