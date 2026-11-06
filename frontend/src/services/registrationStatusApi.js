import api from '../api/api.js'

// Kotak unggah ulang mengikuti urutan tetap; hanya dokumen yang ditandai verifikator (dokumen_ditolak).
export const DOKUMEN_UNGGAH_ULANG = {
  foto_ktm: { jenis: 'foto_ktm', label: 'Foto KTM', accept: '.jpg,.jpeg,image/jpeg', ukuranLabel: '1 MB', kind: 'foto' },
  sk_organisasi: { jenis: 'sk_organisasi', label: 'SK Organisasi', accept: 'application/pdf', ukuranLabel: '2 MB', kind: 'dokumen' },
  sk_kepengurusan: { jenis: 'sk_kepengurusan', label: 'SK Kepengurusan', accept: 'application/pdf', ukuranLabel: '2 MB', kind: 'dokumen' },
}
const URUTAN_DOKUMEN = ['foto_ktm', 'sk_organisasi', 'sk_kepengurusan']

function normalizeStatus(result) {
  const data = result?.data ?? result
  const status = data?.status_pendaftaran ?? 'menunggu'
  const jenisPendaftar = data?.pendaftar?.jenis_pendaftar ?? 'organisasi'
  const dokumenDitolak = Array.isArray(data?.dokumen_ditolak) ? data.dokumen_ditolak : []
  const dokumenDiunggahUlang = status === 'ditolak'
    ? URUTAN_DOKUMEN.filter((jenis) => dokumenDitolak.includes(jenis)).map((jenis) => DOKUMEN_UNGGAH_ULANG[jenis])
    : []

  return {
    jenisPendaftar,
    jenisOrganisasi: data?.organisasi?.jenis_organisasi ?? null,
    namaPendaftar: data?.organisasi?.nama_organisasi ?? data?.pendaftar?.nama ?? '',
    tanggalKirim: data?.tanggal_pendaftaran ?? null,
    status,
    alasanPenolakan: data?.alasan_penolakan ?? null,
    dokumenDitolak,
    dokumenDiunggahUlang,
    dokumen: data?.dokumen ?? [],
    organisasiDetail: data?.organisasi ?? null,
    bisaUnggahUlang: Boolean(data?.bisa_unggah_ulang),
    batasUkuranSkMb: data?.batas_ukuran_sk_mb ?? 2,
  }
}

/**
 * @typedef {Object} RegistrationStatusData
 * @property {'organisasi'|'anggota'} jenisPendaftar
 * @property {string} namaPendaftar
 * @property {string|null} tanggalKirim - ISO 8601 (dari backend) atau YYYY-MM-DD (statusAwal)
 * @property {'menunggu'|'disetujui'|'ditolak'} status
 * @property {Array<{jenis:string,label:string}>} dokumenDiunggahUlang
 * @property {string|null} alasanPenolakan
 */

/**
 * @returns {Promise<{data: RegistrationStatusData|null, error: string|null}>}
 */
export async function getMyRegistrationStatus() {
  try {
    const response = await api.get('/registration/status')
    return { data: normalizeStatus(response.data), error: null }
  } catch (error) {
    const message = error.response?.data?.message ?? 'Status registrasi gagal dimuat. Silakan coba lagi.'
    return { data: null, error: message }
  }
}

/**
 * Unggah ulang foto KTM untuk pendaftar anggota yang ditolak.
 * @param {File} file JPG/JPEG
 * @returns {Promise<{ok: boolean, error: string|null}>}
 */
export async function ajukanUlangFotoKtm(file) {
  const formData = new FormData()
  formData.append('foto_ktm', file)

  try {
    await api.post('/registration/anggota/ajukan-ulang', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return { ok: true, error: null }
  } catch (error) {
    const validationErrors = error.response?.data?.errors
    const message = validationErrors
      ? Object.values(validationErrors).flat()[0]
      : error.response?.data?.message ?? 'Foto KTM gagal dikirim. Silakan coba lagi.'
    return { ok: false, error: message }
  }
}

/**
 * Unggah ulang satu atau beberapa dokumen organisasi dalam satu request.
 * @param {{foto_ktm?: File, sk_organisasi?: File, sk_kepengurusan?: File}} filesMap
 * @returns {Promise<{ok: boolean, error: string|null}>}
 */
export async function resubmitSk(filesMap) {
  const formData = new FormData()
  for (const jenis of URUTAN_DOKUMEN) {
    if (filesMap?.[jenis]) formData.append(jenis, filesMap[jenis])
  }

  try {
    await api.post('/registration/resubmit-sk', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return { ok: true, error: null }
  } catch (error) {
    const validationErrors = error.response?.data?.errors
    const message = validationErrors
      ? Object.values(validationErrors).flat()[0]
      : error.response?.data?.message ?? 'Dokumen gagal dikirim. Silakan coba lagi.'
    return { ok: false, error: message }
  }
}
