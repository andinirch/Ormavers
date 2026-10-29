import api from '../../api/api.js'

export const verificationContexts = {
  administrator: {
    role: 'Administrator',
    organizationId: null,
    organizationName: 'Administrator',
  },
  bem: {
    role: 'Akun Organisasi BEM',
    organizationId: 'bem-fakultas-teknik',
    organizationName: 'BEM',
  },
  hmj: {
    role: 'Akun Organisasi HMJ/UKM',
    organizationId: 'hmif',
    organizationName: 'HMJ',
  },
  anggota: {
    role: 'Anggota',
    organizationId: null,
    organizationName: 'Anggota',
  },
}

function normalizeDocumentType(type = '') {
  return type.replaceAll('_', '-')
}

function normalizeRegistration(item) {
  const rawDocuments = item?.dokumen ?? item?.files ?? []
  const documents = Array.isArray(rawDocuments)
    ? rawDocuments
    : Object.entries(rawDocuments).map(([jenis, value]) => ({
      jenis_dokumen: jenis,
      ...(typeof value === 'string' ? { url: value } : value),
    }))

  return {
    id: item.id_pendaftaran_organisasi,
    parentBemId: item.id_organisasi_induk,
    namaOrganisasi: item.nama_organisasi,
    jenis: item.jenis_organisasi?.toUpperCase() ?? item.jenis,
    jenisLengkap: item.jenis_organisasi,
    tanggal: item.tanggal_pendaftaran,
    tanggalVerifikasi: item.tanggal_verifikasi,
    status: item.status_pendaftaran ?? item.status,
    alasanPenolakan: item.alasan_penolakan ?? '',
    initials: item.nama_organisasi?.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() ?? '',
    kode: item.kode_pendaftaran ?? item.kode,
    nomorTelepon: item.nomor_telepon_organisasi,
    penanggungJawab: item.penanggung_jawab?.nama ?? item.nama,
    nim: item.penanggung_jawab?.nim ?? item.nim,
    whatsapp: item.penanggung_jawab?.nomor_whatsapp ?? item.nomor_whatsapp,
    username: item.penanggung_jawab?.username ?? item.username,
    statusAkun: item.penanggung_jawab?.status ?? item.status_akun,
    dokumen: documents.map((file) => {
      const jenis = normalizeDocumentType(file.jenis_dokumen ?? file.jenis)
      return {
        jenis,
        url: file.url,
        label: jenis === 'sk-organisasi' ? 'SK Organisasi' : jenis === 'sk-kepengurusan' ? 'SK Kepengurusan' : 'Foto selfie dengan KTM',
        file: file.nama_file_asli ?? file.file ?? '',
        size: file.ukuran_kb ? `${(file.ukuran_kb / 1024).toFixed(1)} MB` : '',
        tipe: jenis === 'ktm' ? 'image' : 'pdf',
      }
    }),
  }
}

export async function listHmjRegistrations() {
  const response = await api.get('/org/registrations/hmj-ukm')
  const items = response.data?.data?.pendaftaran ?? response.data?.data ?? []
  return { success: true, data: items.map(normalizeRegistration) }
}

export async function getHmjRegistrationDetail(id) {
  if (!id) throw new Error('ID pendaftaran HMJ/UKM tidak tersedia.')
  const response = await api.get(`/org/registrations/hmj-ukm/${id}`)
  const detail = response.data?.data?.pendaftaran ?? response.data?.pendaftaran ?? response.data?.data
  return { success: true, data: normalizeRegistration(detail) }
}

export async function approveHmjRegistration(id) {
  return api.post(`/org/registrations/hmj-ukm/${id}/approve`)
}

export async function rejectHmjRegistration(id, alasan) {
  return api.post(`/org/registrations/hmj-ukm/${id}/reject`, { alasan })
}

export async function getHmjDocument(id, jenis) {
  return api.get(`/org/registrations/hmj-ukm/${id}/dokumen/${normalizeDocumentType(jenis)}`, { responseType: 'blob' })
}

export async function listMemberRequests() {
  const response = await api.get('/org/member-requests')
  const payload = response.data?.data
  const items = payload?.requests ?? payload?.data ?? payload?.permintaan ?? payload ?? []
  return { success: true, data: items.map((item) => ({ ...item, id: item.id_keanggotaan ?? item.id_permintaan ?? item.id, namaLengkap: item.nama_lengkap ?? item.nama ?? item.pengguna?.nama, nim: item.nim ?? item.pengguna?.nim, tanggal: item.tanggal_pendaftaran ?? item.tanggal_pengajuan ?? item.tanggal, status: item.status_keanggotaan ?? item.status ?? 'menunggu' })) }
}

export async function getMemberRequestDetail(id) {
  if (!id) throw new Error('ID permintaan keanggotaan tidak tersedia.')
  const response = await api.get(`/org/member-requests/${id}`)
  const item = response.data?.data?.request ?? response.data?.data?.permintaan ?? response.data?.data ?? {}
  const ktm = item.dokumen?.ktm
  const ktmFile = typeof ktm === 'object' ? ktm : null
  return {
    success: true,
    data: {
      ...item,
      id: item.id_keanggotaan ?? item.id_permintaan ?? item.id,
      namaLengkap: item.nama_lengkap ?? item.nama ?? item.pengguna?.nama,
      nim: item.nim ?? item.pengguna?.nim,
      tanggal: item.tanggal_pendaftaran ?? item.tanggal_pengajuan ?? item.tanggal,
      tanggalVerifikasi: item.tanggal_verifikasi,
      status: item.status_keanggotaan ?? item.status ?? 'menunggu',
      alasanPenolakan: item.alasan_penolakan ?? '',
      username: item.username ?? item.pengguna?.username,
      fileName: item.file_name ?? item.nama_file ?? ktmFile?.nama_file_asli ?? '',
      fileSize: item.file_size ?? item.ukuran_file ?? (ktmFile?.ukuran_kb ? `${(ktmFile.ukuran_kb / 1024).toFixed(1)} MB` : ''),
      fileUrl: typeof ktm === 'string' ? ktm : ktmFile?.url ?? '',
    },
  }
}

export async function approveMemberRequest(id) { return api.post(`/org/member-requests/${id}/approve`) }
export async function rejectMemberRequest(id, alasan) { return api.post(`/org/member-requests/${id}/reject`, { alasan }) }
export async function getMemberKtm(id) { return api.get(`/org/member-requests/${id}/dokumen/ktm`, { responseType: 'blob' }) }

