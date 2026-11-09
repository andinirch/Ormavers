<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portofolio extends Model
{
    use HasFactory;

    protected $table = 'portofolio';
    protected $primaryKey = 'id_portofolio';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_organisasi',
        'id_event',
        'judul',
        'tahun',
        'deskripsi',
        'dokumentasi',
    ];

    protected function casts(): array
    {
        return [
            'id_event' => 'integer',
            'tahun'    => 'integer',
        ];
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event', 'id');
    }
}
