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

    protected $fillable = [
        'nama', 'username', 'kata_sandi', 'peran', 'status_akun', 'foto_profil',
        'nim', 'nomor_whatsapp', 'foto_ktm',
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
                $pemetaan = [
                    'super_admin'     => 'super_admin',
                    'administrator'   => 'administrator',
                    'anggota'         => 'anggota',
                    'akun_organisasi' => 'akun_bem',
                ];

                $namaRole = $pemetaan[$pengguna->peran] ?? null;

                if ($namaRole) {
                    $role = \Spatie\Permission\Models\Role::findOrCreate($namaRole, 'web');
                    $pengguna->syncRoles($role);
                }
            }
        });
    }

    public function pendaftaranOrganisasi(): HasMany
    {
        return $this->hasMany(PendaftaranOrganisasi::class, 'id_pengguna_pendaftar', 'id_pengguna');
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'id_pengguna', 'id_pengguna');
    }
}