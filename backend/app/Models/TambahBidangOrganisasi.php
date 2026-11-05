<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TambahBidangOrganisasi extends Model
{
    protected $table = 'tambah_bidang_organisasi';
    protected $primaryKey = 'id_tambah_bidang';
    protected $fillable = ['id_organisasi', 'id_bidang', 'nama_bidang_kustom'];

    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    public function organisasi()
    {
        return $this->belongsTo(Organisasi::class, 'id_organisasi', 'id_organisasi');
    }
}