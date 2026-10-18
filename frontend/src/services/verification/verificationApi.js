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

const simulateDelay = (ms = 180) => new Promise((resolve) => setTimeout(resolve, ms))

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
