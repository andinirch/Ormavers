<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PendaftaranOrganisasi extends Model
{
    use HasFactory;

    protected $table = 'pendaftaran_organisasi';
    protected $primaryKey = 'id_pendaftaran_organisasi';

    const CREATED_AT = null;
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_organisasi',
        'status_pendaftaran',
        'alasan_penolakan',
        'id_pengguna_verifikator',
        'tanggal_pendaftaran',
        'tanggal_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pendaftaran' => 'datetime',
            'tanggal_verifikasi'  => 'datetime',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_verifikator', 'id_pengguna');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PendaftaranOrganisasiFile::class, 'id_pendaftaran_organisasi', 'id_pendaftaran_organisasi');
    }
}
