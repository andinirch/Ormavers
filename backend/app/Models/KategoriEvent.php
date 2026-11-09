<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriEvent extends Model
{
    use HasFactory;

    protected $table = 'kategori_event';
    protected $primaryKey = 'id';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'nama_kategori',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'id_kategori', 'id');
    }
}
