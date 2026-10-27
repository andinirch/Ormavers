<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilAnggota extends Model
{
    use HasFactory;

    protected $table = 'profil_anggota';
    protected $primaryKey = 'id_profil_anggota';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_pengguna',
        'nim',
        'nomor_whatsapp',
        'foto_ktm',
        'foto_profil',
        'id_universitas',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function universitas(): BelongsTo
    {
        return $this->belongsTo(Universitas::class, 'id_universitas', 'id_universitas');
    }
}
