<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Pengguna — model autentikasi Voltra (menggantikan model User bawaan).
 * Pasangan email + id_perusahaan bersifat unik (multi-tenant).
 */
class Pengguna extends Authenticatable
{
    use HasApiTokens;

    protected $connection = 'voltra';
    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
