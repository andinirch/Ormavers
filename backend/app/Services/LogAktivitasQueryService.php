<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Carbon\Carbon;

class LogAktivitasQueryService
{
    /**
     * Mengambil daftar log aktivitas dengan filter dan paginasi.
     */
    public function daftarAktivitas(array $filters): array
    {
        $query = LogAktivitas::with(['pengguna.roles']);

        if (! empty($filters['id_pengguna'])) {
            $query->where('id_pengguna', (int) $filters['id_pengguna']);
        }

        if (! empty($filters['jenis'])) {
            $query->where('jenis_aktivitas', $filters['jenis']);
        }

        if (! empty($filters['dari'])) {
            $query->where('waktu_aktivitas', '>=', Carbon::parse($filters['dari'])->startOfDay());
        }

        if (! empty($filters['sampai'])) {
            $query->where('waktu_aktivitas', '<=', Carbon::parse($filters['sampai'])->endOfDay());
        }

        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        $paginated = $query->orderByDesc('waktu_aktivitas')
            ->orderByDesc('id_log')
            ->paginate($perPage);

        $activities = $paginated->getCollection()->map(function (LogAktivitas $log) {
            $pengguna = $log->pengguna;
            $roleName = $pengguna ? ($pengguna->roles->first()?->name ?? $pengguna->peran) : null;

            return [
                'id_log'          => $log->id_log,
                'pengguna'        => $pengguna ? [
                    'id_pengguna' => $pengguna->id_pengguna,
                    'nama'        => $pengguna->nama,
                    'role'        => $roleName,
                ] : null,
                'jenis_aktivitas' => $log->jenis_aktivitas,
                'keterangan'      => $log->keterangan,
                'waktu_aktivitas' => $log->waktu_aktivitas,
            ];
        })->values()->toArray();

        return [
            'activities' => $activities,
            'meta'       => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ];
    }

    /**
     * Mengambil opsi filter jenis aktivitas dan pengguna yang memiliki log.
     */
    public function filterOptions(): array
    {
        $jenis = LogAktivitasService::SEMUA_JENIS;
        sort($jenis);

        $userIds = LogAktivitas::select('id_pengguna')
            ->distinct()
            ->pluck('id_pengguna');

        $pengguna = Pengguna::whereIn('id_pengguna', $userIds)
            ->orderBy('nama')
            ->get(['id_pengguna', 'nama'])
            ->map(fn ($p) => [
                'id_pengguna' => $p->id_pengguna,
                'nama'        => $p->nama,
            ])
            ->values()
            ->toArray();

        return [
            'jenis'    => $jenis,
            'pengguna' => $pengguna,
        ];
    }
}
