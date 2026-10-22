<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keanggotaan extends Model
{
    use HasFactory;

    protected $table = 'keanggotaan';
    protected $primaryKey = 'id_keanggotaan';

    const CREATED_AT = null;
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_pengguna',
        'id_organisasi',
        'status_keanggotaan',
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

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna_verifikator', 'id_pengguna');
    }
}
