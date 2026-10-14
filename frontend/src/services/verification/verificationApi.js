const verificationContexts = {
  administrator: {
    role: 'Administrator',
    organizationId: null,
    organizationName: 'Administrator',
  },
  bemUmg: {
    role: 'Akun Organisasi BEM',
    organizationId: 'bem-umg',
    organizationName: 'BEM UMG',
  },
  hmif: {
    role: 'Akun Organisasi HMJ/UKM',
    organizationId: 'hmif',
    organizationName: 'Himpunan Mahasiswa Informatika (HMIF)',
  },
  ukmRobotika: {
    role: 'Akun Organisasi HMJ/UKM',
    organizationId: 'ukm-robotika',
    organizationName: 'UKM Robotika Teknik',
  },
}

const bem = [
  { id: 'bem-umg', namaOrganisasi: 'BEM UMG', kampus: 'Universitas Muhammadiyah Gresik', tanggal: '10 Okt 2026', status: 'menunggu', kode: 'BEM-UMG-01', nomorTelepon: '081234567890', penanggungJawab: 'Ahmad Fauzi Pratama', nim: '2022410012', whatsapp: '081298765432', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'bem-ums', namaOrganisasi: 'BEM UMS', kampus: 'Universitas Muhammadiyah Surakarta', tanggal: '09 Okt 2026', status: 'menunggu', kode: 'BEM-UMS-02', nomorTelepon: '081234567891', penanggungJawab: 'Dian Pratama', nim: '2022410031', whatsapp: '081298765433', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'bem-umm', namaOrganisasi: 'BEM UMM', kampus: 'Universitas Muhammadiyah Malang', tanggal: '08 Okt 2026', status: 'menunggu', kode: 'BEM-UMM-03', nomorTelepon: '081234567892', penanggungJawab: 'Raka Wijaya', nim: '2022410044', whatsapp: '081298765434', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
]

const hmj = [
  { id: 'reg-ft-004', parentBemId: null, relationshipStatus: 'needs-confirmation', namaOrganisasi: 'Himpunan Mahasiswa Informatika (HMIF)', jenis: 'HMJ', tanggal: '10 Okt 2026', status: 'menunggu', initials: 'HM', kode: 'REG-FT-2026-004', nomorTelepon: '081245678910', penanggungJawab: 'Dimas Bayu Anggoro', nim: '2022510045', whatsapp: '081298765432', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'reg-ft-003', parentBemId: null, relationshipStatus: 'needs-confirmation', namaOrganisasi: 'UKM Robotika Teknik', jenis: 'UKM', tanggal: '09 Okt 2026', status: 'menunggu', initials: 'UR', kode: 'REG-FT-2026-003', nomorTelepon: '081245678911', penanggungJawab: 'Fajar Ramadhan', nim: '2022510046', whatsapp: '081298765433', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'reg-ft-002', parentBemId: null, relationshipStatus: 'needs-confirmation', namaOrganisasi: 'HMJ Teknik Sipil', jenis: 'HMJ', tanggal: '08 Okt 2026', status: 'menunggu', initials: 'TS', kode: 'REG-FT-2026-002', nomorTelepon: '081245678912', penanggungJawab: 'Naufal Hakim', nim: '2022510047', whatsapp: '081298765434', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'reg-kampus-999', parentBemId: null, relationshipStatus: 'needs-confirmation', namaOrganisasi: 'HMJ Organisasi Kampus Lain', jenis: 'HMJ', tanggal: '07 Okt 2026', status: 'menunggu', initials: 'OL', kode: 'REG-OTHER-2026-999', nomorTelepon: '081245678913', penanggungJawab: 'Pengguna Lain', nim: '2022510099', whatsapp: '081298765499', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
]

const members = [
  { id: 'member-001', targetOrganizationId: null, targetOrganizationName: null, relationshipStatus: 'needs-confirmation', namaLengkap: 'Anindya Putri Kirana', nim: '2023510088', tanggal: '10 Okt 2026', status: 'menunggu', initials: 'AP', file: 'KTM_2023510088.jpg' },
  { id: 'member-002', targetOrganizationId: 'hmif', targetOrganizationName: 'Himpunan Mahasiswa Informatika (HMIF)', namaLengkap: 'Reza Maulana Sidiq', nim: '2023510092', tanggal: '09 Okt 2026', status: 'menunggu', initials: 'RM', file: 'KTM_2023510092.jpg' },
  { id: 'member-003', targetOrganizationId: 'ukm-robotika', targetOrganizationName: 'UKM Robotika Teknik', namaLengkap: 'Nabila Zahra Syarif', nim: '2023510104', tanggal: '08 Okt 2026', status: 'menunggu', initials: 'NZ', file: 'KTM_2023510104.jpg' },
]

const clone = (data) => data.map((item) => ({ ...item }))
const stores = { bem: clone(bem), hmj: clone(hmj), member: clone(members) }
const wait = () => new Promise((resolve) => setTimeout(resolve, 250))

function validateContext(type, context) {
  if (!context?.role) return 'Konteks verifikasi tidak tersedia.'
  if (type === 'bem' && context.role !== 'Administrator') return 'Hanya Administrator yang dapat memverifikasi registrasi BEM.'
  if (type === 'hmj' && context.role !== 'Akun Organisasi BEM') return 'Hanya akun organisasi BEM yang dapat memverifikasi HMJ/UKM.'
  if (type === 'member' && !['Akun Organisasi BEM', 'Akun Organisasi HMJ/UKM'].includes(context.role)) return 'Konteks organisasi tidak berwenang memverifikasi anggota.'
  if (['hmj', 'member'].includes(type) && !context.organizationId) return 'ID organisasi aktif tidak tersedia.'
  return null
}

function filterByContext(type, context) {
  if (type === 'bem') return stores.bem
  if (type === 'hmj') return stores.hmj.filter((item) => item.parentBemId === context.organizationId)
  if (type === 'member') return stores.member.filter((item) => item.targetOrganizationId === context.organizationId)
  return []
}

export async function getVerificationList(type, context) {
  await wait()
  const error = validateContext(type, context)
  if (error) return { ok: false, error, data: [] }
  const data = filterByContext(type, context).map((item) => ({ ...item }))
  const relationshipNeedsMapping = ['hmj', 'member'].includes(type) && data.length === 0
  return { ok: true, data, relationshipNeedsMapping }
}

export async function decideVerification(type, id, status, reason = '', context) {
  await wait()
  const error = validateContext(type, context)
  if (error) return { ok: false, error }
  if (!['disetujui', 'ditolak'].includes(status)) return { ok: false, error: 'Keputusan verifikasi tidak valid.' }
  if (status === 'ditolak' && !reason.trim()) return { ok: false, error: 'Alasan penolakan wajib diisi.' }
  const item = filterByContext(type, context).find((entry) => entry.id === id)
  if (!item) return { ok: false, error: 'Pengajuan tidak berada dalam kewenangan organisasi ini.' }
  if (item.status !== 'menunggu') return { ok: false, error: 'Pengajuan sudah diproses.' }
  item.status = status
  item.alasanPenolakan = status === 'ditolak' ? reason.trim() : null
  return { ok: true, data: { ...item } }
}

export { verificationContexts }
