<?php

namespace Tests\Feature;

use App\Models\Keanggotaan;
use App\Models\Organisasi;
use App\Models\PendaftaranOrganisasi;
use App\Models\PendaftaranOrganisasiFile;
use App\Models\Pengguna;
use App\Models\Universitas;
use Database\Seeders\UniversitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UniversitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrasi_berhasil_dan_tabel_riwayat_verifikasi_tidak_ada(): void
    {
        $this->assertTrue(Schema::hasTable('universitas'));
        $this->assertTrue(Schema::hasTable('organisasi'));
        $this->assertTrue(Schema::hasTable('pendaftaran_organisasi'));
        $this->assertTrue(Schema::hasTable('pendaftaran_organisasi_file'));
        $this->assertTrue(Schema::hasTable('keanggotaan'));

        // Tabel riwayat_verifikasi harus sudah di-drop
        $this->assertFalse(Schema::hasTable('riwayat_verifikasi'));

        // Struktur kolom organisasi
        $this->assertTrue(Schema::hasColumn('organisasi', 'id_universitas'));
        $this->assertTrue(Schema::hasColumn('organisasi', 'id_akun_organisasi'));
        $this->assertTrue(Schema::hasColumn('organisasi', 'id_bidang'));
        $this->assertTrue(Schema::hasColumn('organisasi', 'tanggal_mulai_sk'));
        $this->assertTrue(Schema::hasColumn('organisasi', 'tanggal_berakhir_sk'));

        $this->assertFalse(Schema::hasColumn('organisasi', 'id_pengguna_akun'));
        $this->assertFalse(Schema::hasColumn('organisasi', 'nama_instansi'));
        $this->assertFalse(Schema::hasColumn('organisasi', 'nomor_whatsapp'));
        $this->assertFalse(Schema::hasColumn('organisasi', 'portofolio'));
        $this->assertFalse(Schema::hasColumn('organisasi', 'masa_berlaku_sk'));

        // Struktur kolom pendaftaran_organisasi
        $this->assertTrue(Schema::hasColumn('pendaftaran_organisasi', 'id_organisasi'));
        $this->assertTrue(Schema::hasColumn('pendaftaran_organisasi', 'id_pengguna_verifikator'));
        $this->assertFalse(Schema::hasColumn('pendaftaran_organisasi', 'id_pengguna_pendaftar'));
        $this->assertFalse(Schema::hasColumn('pendaftaran_organisasi', 'dibuat_pada'));
    }

    public function test_seeder_universitas_idempoten(): void
    {
        $seeder = new UniversitasSeeder();

        $seeder->run();
        $countAwal = Universitas::count();
        $this->assertEquals(24, $countAwal);

        // Jalankan kedua kali untuk verifikasi idempoten
        $seeder->run();
        $countKedua = Universitas::count();
        $this->assertEquals($countAwal, $countKedua);
    }

    public function test_get_universitas_publik_dan_urut_nama(): void
    {
        Universitas::create(['nama_universitas' => 'Universitas B']);
        Universitas::create(['nama_universitas' => 'Universitas A']);
        Universitas::create(['nama_universitas' => 'Universitas C']);

        $response = $this->getJson('/api/universitas');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar universitas berhasil diambil',
            ])
            ->assertJsonStructure([
                'data' => [
                    'universitas' => [
                        '*' => ['id_universitas', 'nama_universitas', 'logo'],
                    ],
                ],
            ]);

        $daftar = $response->json('data.universitas');
        $this->assertCount(3, $daftar);
        $this->assertEquals('Universitas A', $daftar[0]['nama_universitas']);
        $this->assertEquals('Universitas B', $daftar[1]['nama_universitas']);
        $this->assertEquals('Universitas C', $daftar[2]['nama_universitas']);
    }

    public function test_factory_universitas_file_dan_keanggotaan(): void
    {
        $univ = Universitas::factory()->create();
        $this->assertDatabaseHas('universitas', ['id_universitas' => $univ->id_universitas]);

        $file = PendaftaranOrganisasiFile::factory()->create();
        $this->assertDatabaseHas('pendaftaran_organisasi_file', ['id_file' => $file->id_file]);

        $keanggotaan1 = Keanggotaan::factory()->create();
        $keanggotaan2 = Keanggotaan::factory()->create();

        $this->assertNotEquals($keanggotaan1->id_pengguna, $keanggotaan2->id_pengguna);
        $this->assertDatabaseHas('keanggotaan', ['id_keanggotaan' => $keanggotaan1->id_keanggotaan]);
        $this->assertDatabaseHas('keanggotaan', ['id_keanggotaan' => $keanggotaan2->id_keanggotaan]);
    }
}
