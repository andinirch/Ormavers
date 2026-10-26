<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Pengguna extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

    protected string $guard_name = 'web';

    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    /**
     * Daftar nama role bawaan sistem.
     */
    public const ROLE_BAWAAN = [
        'super_admin', 'administrator', 'akun_bem', 'akun_hmj_ukm', 'anggota',
    ];

    /**
     * Pemetaan kolom peran → nama role Spatie.
     */
    public const PERAN_ROLE_MAP = [
        'super_admin'     => 'super_admin',
        'administrator'   => 'administrator',
        'anggota'         => 'anggota',
        'akun_organisasi' => 'akun_bem',
    ];

    protected $fillable = [
        'nama', 'username', 'kata_sandi', 'peran', 'status_akun',
    ];

    protected $hidden = ['kata_sandi'];

    protected function casts(): array
    {
        return ['kata_sandi' => 'hashed'];
    }

    public function getAuthPassword()
    {
        return $this->kata_sandi;
    }

    protected static function booted(): void
    {
        static::saved(function (Pengguna $pengguna) {
            if ($pengguna->wasChanged('peran') || $pengguna->wasRecentlyCreated) {
                if ($pengguna->peran === 'akun_organisasi' && $pengguna->hasRole('akun_hmj_ukm')) {
                    return;
                }

                $namaRole = self::PERAN_ROLE_MAP[$pengguna->peran] ?? null;

                if ($pengguna->peran === 'akun_organisasi') {
                    $organisasi = $pengguna->relationLoaded('organisasi')
                        ? $pengguna->organisasi
                        : $pengguna->organisasi()->first();

                    if ($organisasi && in_array($organisasi->jenis_organisasi, ['hmj', 'ukm'], true)) {
                        $namaRole = 'akun_hmj_ukm';
                    }
                }

                if ($namaRole) {
                    $role = \Spatie\Permission\Models\Role::findOrCreate($namaRole, 'web');
                    $pengguna->syncRoles($role);
                }
            }
        });
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'id_pengguna', 'id_pengguna');
    }

    public function organisasi(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Organisasi::class, 'id_akun_organisasi', 'id_pengguna');
    }

    public function profilAnggota(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProfilAnggota::class, 'id_pengguna', 'id_pengguna');
    }

    public function getFotoProfilAttribute(): ?string
    {
        return $this->relationLoaded('profilAnggota')
            ? $this->profilAnggota?->foto_profil
            : $this->profilAnggota()->value('foto_profil');
    }

    public function getNimAttribute(): ?string
    {
        return $this->relationLoaded('profilAnggota')
            ? $this->profilAnggota?->nim
            : $this->profilAnggota()->value('nim');
    }

    public function getNomorWhatsappAttribute(): ?string
    {
        return $this->relationLoaded('profilAnggota')
            ? $this->profilAnggota?->nomor_whatsapp
            : $this->profilAnggota()->value('nomor_whatsapp');
    }

    public function getFotoKtmAttribute(): ?string
    {
        return $this->relationLoaded('profilAnggota')
            ? $this->profilAnggota?->foto_ktm
            : $this->profilAnggota()->value('foto_ktm');
    }

    public function keanggotaan(): HasMany
    {
        return $this->hasMany(Keanggotaan::class, 'id_pengguna', 'id_pengguna');
    }
}