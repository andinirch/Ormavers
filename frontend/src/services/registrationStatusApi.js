// TODO(kontrak-be): endpoint status registrasi milik pendaftar yang sedang
// login belum dikonfirmasi backend. Fungsi ini mengembalikan bentuk
// normalisasi UI. Sesuaikan implementasi setelah kontrak tersedia.
//
// TODO(dependensi-auth): kebijakan autentikasi dan cara mengidentifikasi
// registrasi milik pendaftar belum ditetapkan. Jangan menampilkan data
// registrasi pribadi tanpa mekanisme otorisasi yang jelas.

/**
 * @typedef {Object} RegistrationStatusData
 * @property {'organisasi'|'anggota'} jenisPendaftar
 * @property {string} namaPendaftar
 * @property {string} tanggalKirim - ISO date string (YYYY-MM-DD)
 * @property {'menunggu'|'disetujui'|'ditolak'} status
 * @property {string|null} namaDokumenDitolak
 * @property {string|null} alasanPenolakan
 */

/**
 * @returns {Promise<{data: RegistrationStatusData|null, unavailable: boolean, error: string|null}>}
 */
export async function getMyRegistrationStatus() {
  // TODO(kontrak-be): ganti dengan pemanggilan endpoint sesungguhnya
  return { data: null, unavailable: true, error: null }
}

/**
 * @param {File} file
 * @returns {Promise<{ok: boolean, unavailable: boolean, error: string|null}>}
 */
export async function resubmitSk(file) {
  // TODO(kontrak-be): ganti dengan pemanggilan endpoint unggah ulang SK
  void file
  return { ok: false, unavailable: true, error: null }
}
