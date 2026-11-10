<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';
    protected $primaryKey = 'id';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_organisasi',
        'jenis',
        'id_event',
        'id_ajakan',
        'pesan',
        'sudah_baca',
        'dibaca_pada',
    ];

    protected $appends = [
        'id_notifikasi',
        'target_url',
        'target_type',
    ];

    protected function casts(): array
    {
        return [
            'sudah_baca'  => 'boolean',
            'dibaca_pada' => 'datetime',
        ];
    }

    /**
     * Accessor id_notifikasi agar selaras dengan PDM Sprint 4.
     */
    protected function idNotifikasi(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->id,
        );
    }

    /**
     * Helper penentuan URL target navigasi sesuai dengan jenis notifikasi.
     */
    protected function targetUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->jenis) {
                    'ajakan', 'undangan' => '/organisasi/ajakan-masuk' . ($this->id_ajakan ? "?highlight={$this->id_ajakan}" : ''),
                    'jawaban'            => $this->id_event ? "/organisasi/event/{$this->id_event}" : '/organisasi/event',
                    'perubahan_event'    => $this->id_event ? "/events/{$this->id_event}" : '/organisasi/event',
                    'penghapusan_event'  => '/organisasi/event',
                    'pengingat_event'    => $this->id_event ? "/events/{$this->id_event}" : '/organisasi/event',
                    default              => $this->id_event ? "/events/{$this->id_event}" : '/organisasi/event',
                };
            }
        );
    }

    /**
     * Helper penentuan tipe target halaman.
     */
    protected function targetType(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->jenis) {
                    'ajakan', 'undangan' => 'ajakan_masuk',
                    'jawaban'            => 'kelola_event',
                    'perubahan_event'    => 'detail_event',
                    'penghapusan_event'  => 'kelola_event',
                    'pengingat_event'    => 'detail_event',
                    default              => 'kelola_event',
                };
            }
        );
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event', 'id');
    }

    public function ajakan(): BelongsTo
    {
        return $this->belongsTo(Ajakan::class, 'id_ajakan', 'id');
    }
}
