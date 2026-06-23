<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioLatar extends Model
{
    protected $table = 'audio_latar';
    protected $primaryKey = 'id_audio_latar';
    public $timestamps = true;

    protected $fillable = [
        'nama_audio',
        'path_file'
    ];

    public function halaman()
    {
        return $this->hasMany(Halaman::class, 'id_audio_latar', 'id_audio_latar');
    }
}
