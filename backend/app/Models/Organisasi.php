<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organisasi extends Model
{
    use HasFactory;

    protected $table = 'organisasi';
    protected $primaryKey = 'id_organisasi';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_universitas',
        'id_akun_organisasi',
        'id_bidang',
        'nama_organisasi',
        'jenis_organisasi',
        'nomor_telepon',
        'deskripsi',
        'alamat',
        'logo',
        'tanggal_mulai_sk',
        'tanggal_berakhir_sk',
        'status_organisasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai_sk'    => 'date',
            'tanggal_berakhir_sk' => 'date',
        ];
    }

    public function universitas(): BelongsTo
    {
        return $this->belongsTo(Universitas::class, 'id_universitas', 'id_universitas');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(BidangOrganisasi::class, 'id_bidang', 'id_bidang');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_akun_organisasi', 'id_pengguna');
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_organisasi_induk', 'id_organisasi');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'id_organisasi_induk', 'id_organisasi');
    }

    public function pendaftaran(): HasOne
    {
        return $this->hasOne(PendaftaranOrganisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function keanggotaan(): HasMany
    {
        return $this->hasMany(Keanggotaan::class, 'id_organisasi', 'id_organisasi');
    }

    public function portofolio(): HasMany
    {
        return $this->hasMany(Portofolio::class, 'id_organisasi', 'id_organisasi');
    }

    public function kontak(): HasMany
    {
        return $this->hasMany(KontakOrganisasi::class, 'id_organisasi', 'id_organisasi');
    }
}
