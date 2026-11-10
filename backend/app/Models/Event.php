<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    use HasFactory;

    protected $table = 'event';
    protected $primaryKey = 'id';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_organisasi',
        'id_kategori',
        'judul',
        'deskripsi',
        'tanggal_event',
        'tag',
        'tautan_pendaftaran',
        'periode_mulai',
        'periode_selesai',
        'jangkauan_publikasi',
        'token_sharelink',
    ];

    protected $appends = [
        'status_pendaftaran',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_event'   => 'date',
            'periode_mulai'   => 'datetime',
            'periode_selesai' => 'datetime',
        ];
    }

    /**
     * Hitung status pendaftaran otomatis berdasarkan waktu saat ini:
     * - Belum Dibuka : now < periode_mulai
     * - Dibuka       : periode_mulai <= now <= periode_selesai
     * - Ditutup      : now > periode_selesai
     */
    public function getStatusPendaftaranAttribute(): string
    {
        $now = Carbon::now();

        if ($this->periode_mulai && $now->lt($this->periode_mulai)) {
            return 'Belum Dibuka';
        }

        if ($this->periode_selesai && $now->gt($this->periode_selesai)) {
            return 'Ditutup';
        }

        return 'Dibuka';
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriEvent::class, 'id_kategori', 'id');
    }

    public function posters(): HasMany
    {
        return $this->hasMany(EventPoster::class, 'id_event', 'id');
    }

    public function posterUtama(): HasOne
    {
        return $this->hasOne(EventPoster::class, 'id_event', 'id')->where('is_utama', true);
    }

    public function ajakan(): HasMany
    {
        return $this->hasMany(Ajakan::class, 'id_event', 'id');
    }

    public function kolaborator(): HasMany
    {
        return $this->hasMany(Ajakan::class, 'id_event', 'id')
            ->where('jenis', 'kolaborator')
            ->where('status', 'diterima');
    }

    public function undangan(): HasMany
    {
        return $this->hasMany(Ajakan::class, 'id_event', 'id')
            ->where('jenis', 'undangan')
            ->where('status', 'diterima');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_event', 'id');
    }
}
