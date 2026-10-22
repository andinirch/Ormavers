<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Universitas extends Model
{
    use HasFactory;

    protected $table = 'universitas';
    protected $primaryKey = 'id_universitas';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'nama_universitas',
        'logo',
    ];

    public function organisasi(): HasMany
    {
        return $this->hasMany(Organisasi::class, 'id_universitas', 'id_universitas');
    }
}
