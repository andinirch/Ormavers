// Menyimpan status awal (menunggu) setelah registrasi, agar halaman status tetap tampil saat refresh.
// Data ini hanya di sessionStorage (satu tab) dan dihapus setelah user login.
const STATUS_AWAL_KEY = 'ormavers:status-awal'

export function simpanStatusAwal({ jenisPendaftar, jenisOrganisasi = null, namaPendaftar }) {
  const data = {
    jenisPendaftar,
    jenisOrganisasi,
    namaPendaftar: namaPendaftar ?? '',
    tanggalKirim: new Date().toISOString().slice(0, 10),
    status: 'menunggu',
    alasanPenolakan: null,
    jenisDokumenDitolak: null,
    namaDokumenDitolak: null,
    dokumen: [],
    organisasiDetail: null,
    bisaUnggahUlang: false,
    batasUkuranSkMb: 2,
  }
  try {
    sessionStorage.setItem(STATUS_AWAL_KEY, JSON.stringify(data))
  } catch {
    // sessionStorage tidak tersedia: halaman status akan meminta login
  }
  return data
}

export function ambilStatusAwal() {
  try {
    const raw = sessionStorage.getItem(STATUS_AWAL_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export function hapusStatusAwal() {
  try {
    sessionStorage.removeItem(STATUS_AWAL_KEY)
  } catch {
    // abaikan
  }
}
