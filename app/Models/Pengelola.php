<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pengelola extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'pengelola';
    protected $primaryKey = 'id_pengelola';
    
    protected $fillable = ['nama_pengelola', 'username', 'password'];
    protected $hidden = ['password'];

    public function buku()
    {
        return $this->hasMany(Buku::class, 'id_pengelola', 'id_pengelola');
    }
}
