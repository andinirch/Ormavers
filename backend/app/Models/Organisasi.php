<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisasi extends Model
{
    protected $table = 'organisasi';
    protected $primaryKey = 'id_organisasi';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_pengguna_akun',
        'id_organisasi_induk',
        'nama_organisasi',
        'jenis_organisasi',
        'nama_instansi',
        'nomor_telepon',
        'nomor_whatsapp',
        'deskripsi',
        'alamat',
        'logo',
        'portofolio',
        'masa_berlaku_sk',
        'status_organisasi',
    ];

    protected function casts(): array
    {
        return [
            'masa_berlaku_sk' => 'date',
        ];
    }

    public function akunPengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_akun', 'id_pengguna');
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_organisasi_induk', 'id_organisasi');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'id_organisasi_induk', 'id_organisasi');
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(PendaftaranOrganisasi::class, 'id_organisasi', 'id_organisasi');
    }
}
