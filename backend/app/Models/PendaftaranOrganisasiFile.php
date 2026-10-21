<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendaftaranOrganisasiFile extends Model
{
    use HasFactory;

    protected $table = 'pendaftaran_organisasi_file';
    protected $primaryKey = 'id_file';

    public $timestamps = false;

    protected $fillable = [
        'id_pendaftaran_organisasi',
        'jenis_dokumen',
        'nama_file_asli',
        'path_file',
        'ukuran_kb',
        'diunggah_pada',
    ];

    protected function casts(): array
    {
        return [
            'ukuran_kb'     => 'integer',
            'diunggah_pada' => 'datetime',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(PendaftaranOrganisasi::class, 'id_pendaftaran_organisasi', 'id_pendaftaran_organisasi');
    }
}
