<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudioLatar extends Model
{
    use HasFactory;

    protected $table = 'audio_latar';

    protected $primaryKey = 'id_audio_latar';

    protected $fillable = [
        'nama_audio',
        'path_file',
    ];

    public function halaman(): HasMany
    {
        return $this->hasMany(Halaman::class, 'id_audio_latar', 'id_audio_latar');
    }
}