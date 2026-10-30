import api from '../api/api.js'

function normalizeStatus(result) {
  const data = result?.data ?? result
  const status = data?.status_pendaftaran ?? 'menunggu'
  const jenisPendaftar = data?.pendaftar?.jenis_pendaftar ?? 'organisasi'
  const dokumenDitolak = data?.dokumen?.find((item) => item.jenis_dokumen === 'sk_organisasi' || item.jenis_dokumen === 'sk_kepengurusan')
  const labelDokumen = {
    sk_organisasi: 'SK Organisasi',
    sk_kepengurusan: 'SK Kepengurusan',
  }

  return {
    jenisPendaftar,
    jenisOrganisasi: data?.organisasi?.jenis_organisasi ?? null,
    namaPendaftar: data?.organisasi?.nama_organisasi ?? data?.pendaftar?.nama ?? '',
    tanggalKirim: data?.tanggal_pendaftaran ?? null,
    status,
    alasanPenolakan: data?.alasan_penolakan ?? null,
    jenisDokumenDitolak: dokumenDitolak?.jenis_dokumen ?? null,
    namaDokumenDitolak: labelDokumen[dokumenDitolak?.jenis_dokumen] ?? null,
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
 * @property {string} tanggalKirim - ISO date string (YYYY-MM-DD)
 * @property {'menunggu'|'disetujui'|'ditolak'} status
 * @property {string|null} namaDokumenDitolak
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
 * @param {File} file
 * @param {'sk_organisasi'|'sk_kepengurusan'} documentType
 * @returns {Promise<{ok: boolean, error: string|null}>}
 */
export async function resubmitSk(file, documentType = 'sk_kepengurusan') {
  const formData = new FormData()
  formData.append(documentType, file)

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
