<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PendaftaranOrganisasi extends Model
{
    protected $table = 'pendaftaran_organisasi';
    protected $primaryKey = 'id_pendaftaran_organisasi';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_organisasi',
        'id_pengguna_pendaftar',
        'jenis_organisasi',
        'nama_organisasi',
        'nama_instansi',
        'nomor_telepon_organisasi',
        'nama_penanggung_jawab',
        'nim_penanggung_jawab',
        'nomor_whatsapp',
        'foto_ktm',
        'dokumen_sk_organisasi',
        'dokumen_sk_kepengurusan',
        'status_pendaftaran',
        'alasan_penolakan',
        'tanggal_verifikasi',
        'tanggal_pendaftaran',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pendaftaran' => 'datetime',
            'tanggal_verifikasi'  => 'datetime',
        ];
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_pendaftar', 'id_pengguna');
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function riwayatVerifikasi(): HasMany
    {
        return $this->hasMany(RiwayatVerifikasi::class, 'id_pendaftaran_organisasi', 'id_pendaftaran_organisasi');
    }
}
