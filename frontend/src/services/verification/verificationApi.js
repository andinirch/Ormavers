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

const mockHmjRegistrations = [
  {
    id: 'reg-ft-2026-004',
    parentBemId: 'bem-fakultas-teknik',
    namaOrganisasi: 'Himpunan Mahasiswa Informatika (HMIF)',
    jenis: 'HMJ',
    jenisLengkap: 'HMJ (Himpunan Mahasiswa Jurusan)',
    tanggal: '10 Okt 2026',
    status: 'menunggu',
    initials: 'HM',
    kode: 'REG-FT-2026-004',
    nomorTelepon: '081245678910',
    penanggungJawab: 'Dimas Bayu Anggoro',
    nim: '2022510045',
    whatsapp: '081298765432',
    dokumen: [
      { jenis: 'ktm', label: 'Foto selfie dengan KTM', file: 'KTM-DIMAS-BAYU-2022510045.jpg', size: '1.8 MB', tipe: 'image' },
      { jenis: 'sk_organisasi', label: 'SK Organisasi', file: 'SK_Organisasi_HMIF_2026.pdf', size: '2.1 MB', tipe: 'pdf' },
      { jenis: 'sk_kepengurusan', label: 'SK Kepengurusan', file: 'SK_Kepengurusan_HMIF_2026.pdf', size: '1.5 MB', tipe: 'pdf' },
    ],
  },
  {
    id: 'reg-ft-2026-003',
    parentBemId: 'bem-fakultas-teknik',
    namaOrganisasi: 'UKM Robotika Teknik',
    jenis: 'UKM',
    jenisLengkap: 'UKM (Unit Kegiatan Mahasiswa)',
    tanggal: '09 Okt 2026',
    status: 'menunggu',
    initials: 'UR',
    kode: 'REG-FT-2026-003',
    nomorTelepon: '081245678911',
    penanggungJawab: 'Fajar Ramadhan',
    nim: '2022510046',
    whatsapp: '081298765433',
    dokumen: [
      { jenis: 'ktm', label: 'Foto selfie dengan KTM', file: 'KTM-FAJAR-2022510046.jpg', size: '1.6 MB', tipe: 'image' },
      { jenis: 'sk_organisasi', label: 'SK Organisasi', file: 'SK_Organisasi_Robotika_2026.pdf', size: '2.4 MB', tipe: 'pdf' },
      { jenis: 'sk_kepengurusan', label: 'SK Kepengurusan', file: 'SK_Kepengurusan_Robotika_2026.pdf', size: '1.8 MB', tipe: 'pdf' },
    ],
  },
  {
    id: 'reg-ft-2026-002',
    parentBemId: 'bem-fakultas-teknik',
    namaOrganisasi: 'HMJ Teknik Sipil',
    jenis: 'HMJ',
    jenisLengkap: 'HMJ (Himpunan Mahasiswa Jurusan)',
    tanggal: '08 Okt 2026',
    status: 'menunggu',
    initials: 'TS',
    kode: 'REG-FT-2026-002',
    nomorTelepon: '081245678912',
    penanggungJawab: 'Naufal Hakim',
    nim: '2022510047',
    whatsapp: '081298765434',
    dokumen: [
      { jenis: 'ktm', label: 'Foto selfie dengan KTM', file: 'KTM-NAUFAL-2022510047.jpg', size: '1.9 MB', tipe: 'image' },
      { jenis: 'sk_organisasi', label: 'SK Organisasi', file: 'SK_Organisasi_Sipil_2026.pdf', size: '2.0 MB', tipe: 'pdf' },
      { jenis: 'sk_kepengurusan', label: 'SK Kepengurusan', file: 'SK_Kepengurusan_Sipil_2026.pdf', size: '1.4 MB', tipe: 'pdf' },
    ],
  },
]

const mockMemberRequests = [
  {
    id: 'member-001',
    targetOrganizationId: 'hmif',
    targetRole: 'Akun Organisasi HMJ/UKM',
    namaLengkap: 'Anindya Putri Kirana',
    nim: '2023510088',
    tanggal: '10 Okt 2026',
    status: 'menunggu',
    initials: 'AP',
    fileName: 'KTM_2023510088.jpg',
    fileSize: '1.4 MB',
    fileDate: 'Terunggah 10 Okt 2026',
  },
  {
    id: 'member-002',
    targetOrganizationId: 'hmif',
    targetRole: 'Akun Organisasi HMJ/UKM',
    namaLengkap: 'Reza Maulana Sidiq',
    nim: '2023510092',
    tanggal: '09 Okt 2026',
    status: 'menunggu',
    initials: 'RM',
    fileName: 'KTM_2023510092.jpg',
    fileSize: '1.2 MB',
    fileDate: 'Terunggah 09 Okt 2026',
  },
  {
    id: 'member-003',
    targetOrganizationId: 'hmif',
    targetRole: 'Akun Organisasi HMJ/UKM',
    namaLengkap: 'Nabila Zahra Syarif',
    nim: '2023510104',
    tanggal: '08 Okt 2026',
    status: 'menunggu',
    initials: 'NZ',
    fileName: 'KTM_2023510104.jpg',
    fileSize: '1.5 MB',
    fileDate: 'Terunggah 08 Okt 2026',
  },
]

const mockBemMemberRequests = [
  {
    id: 'member-bem-001',
    targetOrganizationId: 'bem-fakultas-teknik',
    targetRole: 'Akun Organisasi BEM',
    namaLengkap: 'Bagas Aditya Pratama',
    nim: '2023510012',
    tanggal: '10 Okt 2026',
    status: 'menunggu',
    initials: 'BA',
    fileName: 'KTM_2023510012.jpg',
    fileSize: '1.3 MB',
    fileDate: 'Terunggah 10 Okt 2026',
  },
  {
    id: 'member-bem-002',
    targetOrganizationId: 'bem-fakultas-teknik',
    targetRole: 'Akun Organisasi BEM',
    namaLengkap: 'Citra Dewi Anggraini',
    nim: '2023510044',
    tanggal: '09 Okt 2026',
    status: 'menunggu',
    initials: 'CD',
    fileName: 'KTM_2023510044.jpg',
    fileSize: '1.1 MB',
    fileDate: 'Terunggah 09 Okt 2026',
  },
]

const stores = {
  hmj: mockHmjRegistrations.map((item) => ({ ...item })),
  memberHmj: mockMemberRequests.map((item) => ({ ...item })),
  memberBem: mockBemMemberRequests.map((item) => ({ ...item })),
}

import api from '../../api/api.js'

const simulateDelay = (ms = 180) => new Promise((resolve) => setTimeout(resolve, ms))

function normalizeRegistration(item) {
  const rawDocuments = item?.dokumen ?? item?.files ?? []
  const documents = Array.isArray(rawDocuments)
    ? rawDocuments
    : Object.entries(rawDocuments).map(([jenis, url]) => ({ jenis_dokumen: jenis, url }))

  return {
    id: item.id_pendaftaran_organisasi,
    parentBemId: item.id_organisasi_induk,
    namaOrganisasi: item.nama_organisasi,
    jenis: item.jenis_organisasi?.toUpperCase() ?? item.jenis,
    jenisLengkap: item.jenis_organisasi,
    tanggal: item.tanggal_pendaftaran,
    status: item.status_pendaftaran ?? item.status,
    initials: item.nama_organisasi?.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() ?? '',
    kode: item.kode_pendaftaran ?? item.kode,
    nomorTelepon: item.nomor_telepon_organisasi,
    penanggungJawab: item.penanggung_jawab?.nama ?? item.nama,
    nim: item.penanggung_jawab?.nim ?? item.nim,
    whatsapp: item.penanggung_jawab?.nomor_whatsapp ?? item.nomor_whatsapp,
    dokumen: documents.map((file) => ({
      jenis: file.jenis_dokumen ?? file.jenis,
      url: file.url,
      label: file.jenis_dokumen === 'sk_organisasi' ? 'SK Organisasi' : file.jenis_dokumen === 'sk_kepengurusan' ? 'SK Kepengurusan' : 'Foto selfie dengan KTM',
      file: file.nama_file_asli ?? file.file,
      size: file.ukuran_kb ? `${(file.ukuran_kb / 1024).toFixed(1)} MB` : '',
      tipe: (file.jenis_dokumen ?? file.jenis) === 'ktm' ? 'image' : 'pdf',
    })),
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
  return api.get(`/org/registrations/hmj-ukm/${id}/dokumen/${jenis}`, { responseType: 'blob' })
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
  const item = response.data?.data?.request ?? response.data?.data?.permintaan ?? response.data?.data
  return { success: true, data: { ...item, id: item.id_keanggotaan ?? item.id_permintaan ?? item.id, namaLengkap: item.nama_lengkap ?? item.nama ?? item.pengguna?.nama, nim: item.nim ?? item.pengguna?.nim, tanggal: item.tanggal_pendaftaran ?? item.tanggal_pengajuan ?? item.tanggal, status: item.status_keanggotaan ?? item.status ?? 'menunggu', fileName: item.file_name ?? item.nama_file ?? item.pengguna?.foto_ktm, fileSize: item.file_size ?? item.ukuran_file } }
}

export async function approveMemberRequest(id) { return api.post(`/org/member-requests/${id}/approve`) }
export async function rejectMemberRequest(id, alasan) { return api.post(`/org/member-requests/${id}/reject`, { alasan }) }
export async function getMemberKtm(id) { return api.get(`/org/member-requests/${id}/dokumen/ktm`, { responseType: 'blob' }) }

export async function listMockRegistrations(type) {
  await simulateDelay()
  const source = type === 'hmj'
    ? stores.hmj
    : type === 'member-bem'
      ? stores.memberBem
      : stores.memberHmj
  return {
    success: true,
    data: source.map((entry) => ({ ...entry })),
  }
}

export async function getMockRegistrationDetail(type, id) {
  await simulateDelay()
  const source = type === 'hmj'
    ? stores.hmj
    : type === 'member-bem'
      ? stores.memberBem
      : stores.memberHmj
  const found = source.find((item) => item.id === id)
  if (!found) {
    return { success: false, message: 'Data tidak ditemukan' }
  }
  return { success: true, data: { ...found } }
}

export async function approveMockRegistration(type, id) {
  await simulateDelay()
  const source = type === 'hmj'
    ? stores.hmj
    : type === 'member-bem'
      ? stores.memberBem
      : stores.memberHmj
  const index = source.findIndex((item) => item.id === id)
  if (index === -1) {
    return { success: false, message: 'Data pendaftaran tidak ditemukan' }
  }
  source[index].status = 'disetujui'
  return { success: true, data: { ...source[index] } }
}

export async function rejectMockRegistration(type, id, alasan) {
  await simulateDelay()
  if (!alasan || !alasan.trim()) {
    return { success: false, message: 'Alasan penolakan wajib diisi' }
  }
  const source = type === 'hmj'
    ? stores.hmj
    : type === 'member-bem'
      ? stores.memberBem
      : stores.memberHmj
  const index = source.findIndex((item) => item.id === id)
  if (index === -1) {
    return { success: false, message: 'Data pendaftaran tidak ditemukan' }
  }
  source[index].status = 'ditolak'
  source[index].alasanPenolakan = alasan.trim()
  return { success: true, data: { ...source[index] } }
}
