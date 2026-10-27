<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BidangOrganisasi extends Model
{
    use HasFactory;

    protected $table = 'bidang_organisasi';
    protected $primaryKey = 'id_bidang';

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'nama_bidang',
    ];

    public function organisasi(): HasMany
    {
        return $this->hasMany(Organisasi::class, 'id_bidang', 'id_bidang');
    }
}
