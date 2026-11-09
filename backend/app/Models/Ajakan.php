<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ajakan extends Model
{
    use HasFactory;

    protected $table = 'ajakan';
    protected $primaryKey = 'id';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_event',
        'id_organisasi_pengirim',
        'id_organisasi_tujuan',
        'jenis',
        'status',
        'alasan_penolakan',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event', 'id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi_pengirim', 'id_organisasi');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi_tujuan', 'id_organisasi');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_ajakan', 'id');
    }
}
