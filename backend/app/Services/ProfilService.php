<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\ProfilAnggota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfilService
{
    /**
     * Ambil data profil pengguna.
     */
    public function getProfil(Pengguna $pengguna): array
    {
        $pengguna->loadMissing(['roles', 'keanggotaan.organisasi', 'profilAnggota']);

        $roleName = $pengguna->roles->first()?->name ?? $pengguna->peran;
        $profil   = $pengguna->profilAnggota;

        $fotoUrl = null;
        if ($profil?->foto_profil) {
            $fotoUrl = Storage::disk('public')->url($profil->foto_profil);
        }

        $organisasiDiikuti = $pengguna->keanggotaan
            ? $pengguna->keanggotaan
                ->filter(fn ($k) => in_array($k->status_keanggotaan, ['disetujui', 'aktif'], true))
                ->map(fn ($k) => [
                    'id_keanggotaan'      => $k->id_keanggotaan,
                    'id_organisasi'       => $k->id_organisasi,
                    'nama_organisasi'     => $k->organisasi?->nama_organisasi,
                    'jenis_organisasi'    => $k->organisasi?->jenis_organisasi,
                    'status_keanggotaan'  => $k->status_keanggotaan,
                    'tanggal_pendaftaran' => $k->tanggal_pendaftaran?->toISOString(),
                    'tanggal_verifikasi'  => $k->tanggal_verifikasi?->toISOString(),
                ])
                ->values()
                ->all()
            : [];

        return [
            'id_pengguna'        => $pengguna->id_pengguna,
            'nama'               => $pengguna->nama,
            'username'           => $pengguna->username,
            'nim'                => $profil?->nim,
            'role'               => $roleName,
            'peran'              => $pengguna->peran,
            'status_akun'        => $pengguna->status_akun,
            'nomor_whatsapp'     => $profil?->nomor_whatsapp,
            'foto_profil_url'    => $fotoUrl,
            'organisasi_diikuti' => $organisasiDiikuti,
            'dibuat_pada'        => $pengguna->dibuat_pada,
        ];
    }

    /**
     * Perbarui data nama dan nomor whatsapp pengguna.
     */
    public function updateProfil(Pengguna $pengguna, array $data): array
    {
        $pengguna->update([
            'nama' => $data['nama'],
        ]);

        ProfilAnggota::updateOrCreate(
            ['id_pengguna' => $pengguna->id_pengguna],
            ['nomor_whatsapp' => $data['nomor_whatsapp'] ?? null]
        );

        return $this->getProfil($pengguna->fresh());
    }

    /**
     * Ubah kata sandi pengguna dan cabut token lain.
     *
     * @throws ValidationException
     */
    public function updatePassword(Pengguna $pengguna, array $data): void
    {
        if (! Hash::check($data['password_lama'], $pengguna->kata_sandi)) {
            throw ValidationException::withMessages([
                'password_lama' => ['Password lama tidak sesuai'],
            ]);
        }

        $pengguna->update([
            'kata_sandi' => $data['password_baru'],
        ]);

        // Cabut token lain milik pengguna, pertahankan token saat ini
        $currentTokenId = $pengguna->currentAccessToken()?->id;
        if ($currentTokenId) {
            $pengguna->tokens()->where('id', '!=', $currentTokenId)->delete();
        }
    }

    /**
     * Unggah foto profil pengguna ke disk public dan hapus foto lama.
     */
    public function uploadFoto(Pengguna $pengguna, UploadedFile $file): array
    {
        $profil = $pengguna->profilAnggota;

        // Hapus foto lama jika ada di disk public
        if ($profil?->foto_profil && Storage::disk('public')->exists($profil->foto_profil)) {
            Storage::disk('public')->delete($profil->foto_profil);
        }

        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('foto-profil', $filename, 'public');

        ProfilAnggota::updateOrCreate(
            ['id_pengguna' => $pengguna->id_pengguna],
            ['foto_profil' => $path]
        );

        return [
            'foto_profil_url' => Storage::disk('public')->url($path),
        ];
    }
}
