<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPoster extends Model
{
    use HasFactory;

    protected $table = 'event_poster';
    protected $primaryKey = 'id';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_event',
        'nama_file_asli',
        'path_file',
        'ukuran_file',
        'is_utama',
    ];

    protected function casts(): array
    {
        return [
            'ukuran_file' => 'integer',
            'is_utama'    => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'id_event', 'id');
    }
}
