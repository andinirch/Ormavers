<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatVerifikasi extends Model
{
    protected $table = 'riwayat_verifikasi';
    protected $primaryKey = 'id_riwayat_verifikasi';

    public $timestamps = false;

    protected $fillable = [
        'id_pendaftaran_organisasi',
        'id_pendaftaran_anggota',
        'id_pengguna_verifikator',
        'status_sebelumnya',
        'status_sesudahnya',
        'catatan_verifikasi',
        'tanggal_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_verifikasi' => 'datetime',
        ];
    }

    public function pendaftaranOrganisasi(): BelongsTo
    {
        return $this->belongsTo(PendaftaranOrganisasi::class, 'id_pendaftaran_organisasi', 'id_pendaftaran_organisasi');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_verifikator', 'id_pengguna');
    }
}
