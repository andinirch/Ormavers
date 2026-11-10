<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Notifikasi;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KirimPengingatEventH7 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:kirim-pengingat-h7 {--date= : Tanggal referensi untuk simulasi pengecekan (format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengingat event H-7 kepada pembuat event dan kolaborator yang diterima';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->addDays(7)->toDateString()
            : Carbon::today()->addDays(7)->toDateString();

        $this->info("Memeriksa event dengan tanggal pelaksanaan: {$targetDate} (H-7)...");

        // Ambil event yang memiliki tanggal pelaksanaan tepat H-7
        $events = Event::with(['kolaborator'])
            ->whereDate('tanggal_event', $targetDate)
            ->get();

        if ($events->isEmpty()) {
            $this->info("Tidak ada event yang berlangsung pada tanggal {$targetDate}.");
            return self::SUCCESS;
        }

        $totalNotifikasiDibuat = 0;
        $totalEventDiproses = 0;

        foreach ($events as $event) {
            $penerimaOrgIds = [];

            // 1. Pembuat event
            if ($event->id_organisasi) {
                $penerimaOrgIds[] = (int) $event->id_organisasi;
            }

            // 2. Kolaborator dengan status 'diterima'
            foreach ($event->kolaborator as $kolaborator) {
                if ($kolaborator->id_organisasi_tujuan) {
                    $penerimaOrgIds[] = (int) $kolaborator->id_organisasi_tujuan;
                }
            }

            $penerimaOrgIds = array_values(array_unique(array_filter($penerimaOrgIds)));
            $formattedTanggal = Carbon::parse($event->tanggal_event)->locale('id')->isoFormat('D MMMM Y');

            foreach ($penerimaOrgIds as $orgId) {
                // Idempotency: periksa apakah notifikasi pengingat untuk event & organisasi ini sudah pernah dibuat
                $sudahAda = Notifikasi::where('id_organisasi', $orgId)
                    ->where('id_event', $event->id)
                    ->where('jenis', 'pengingat_event')
                    ->exists();

                if (! $sudahAda) {
                    Notifikasi::create([
                        'id_organisasi' => $orgId,
                        'jenis'         => 'pengingat_event',
                        'id_event'      => $event->id,
                        'id_ajakan'     => null,
                        'pesan'         => "Pengingat: Event '{$event->judul}' akan berlangsung dalam 7 hari (pada {$formattedTanggal}).",
                        'sudah_baca'    => false,
                    ]);

                    $totalNotifikasiDibuat++;
                }
            }

            $totalEventDiproses++;
        }

        $this->info("Selesai memproses {$totalEventDiproses} event. Sebanyak {$totalNotifikasiDibuat} notifikasi pengingat berhasil dikirim.");

        return self::SUCCESS;
    }
}
