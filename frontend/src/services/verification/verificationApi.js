const bem = [
  { id: 'bem-surabaya-01', namaOrganisasi: 'BEM Fakultas Teknik', kampus: 'Universitas Muhammadiyah Surabaya', tanggal: '10 Okt 2026', status: 'menunggu', kode: 'BEM-SURABAYA-01', nomorTelepon: '081234567890', penanggungJawab: 'Ahmad Fauzi Pratama', nim: '2022410012', whatsapp: '081298765432', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'bem-malang-02', namaOrganisasi: 'BEM Fakultas Ekonomi & Bisnis', kampus: 'Universitas Muhammadiyah Malang', tanggal: '09 Okt 2026', status: 'menunggu', sublabel: 'Fakultas Reguler', kode: 'BEM-MALANG-02', nomorTelepon: '081234567891', penanggungJawab: 'Dian Pratama', nim: '2022410031', whatsapp: '081298765433', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'bem-sidoarjo-03', namaOrganisasi: 'BEM Fakultas Hukum', kampus: 'Universitas Muhammadiyah Sidoarjo', tanggal: '08 Okt 2026', status: 'menunggu', sublabel: 'Fakultas Reguler', kode: 'BEM-SIDOARJO-03', nomorTelepon: '081234567892', penanggungJawab: 'Raka Wijaya', nim: '2022410044', whatsapp: '081298765434', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
]
const hmj = [
  { id: 'reg-ft-004', namaOrganisasi: 'Himpunan Mahasiswa Informatika (HMIF)', jenis: 'HMJ', tanggal: '10 Okt 2026', status: 'menunggu', initials: 'HM', kode: 'REG-FT-2026-004', nomorTelepon: '081245678910', penanggungJawab: 'Dimas Bayu Anggoro', nim: '2022510045', whatsapp: '081298765432', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'reg-ft-003', namaOrganisasi: 'UKM Robotika Teknik', jenis: 'UKM', tanggal: '09 Okt 2026', status: 'menunggu', initials: 'UR', kode: 'REG-FT-2026-003', nomorTelepon: '081245678911', penanggungJawab: 'Fajar Ramadhan', nim: '2022510046', whatsapp: '081298765433', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
  { id: 'reg-ft-002', namaOrganisasi: 'HMJ Teknik Sipil', jenis: 'HMJ', tanggal: '08 Okt 2026', status: 'menunggu', initials: 'TS', kode: 'REG-FT-2026-002', nomorTelepon: '081245678912', penanggungJawab: 'Naufal Hakim', nim: '2022510047', whatsapp: '081298765434', dokumen: ['SK Organisasi', 'SK Kepengurusan'] },
]
const members = [
  { id: 'member-001', namaLengkap: 'Anindya Putri Kirana', nim: '2023510088', tanggal: '10 Okt 2026', status: 'menunggu', initials: 'AP', file: 'KTM_2023510088.jpg' },
  { id: 'member-002', namaLengkap: 'Reza Maulana Sidiq', nim: '2023510092', tanggal: '09 Okt 2026', status: 'menunggu', initials: 'RM', file: 'KTM_2023510092.jpg' },
  { id: 'member-003', namaLengkap: 'Nabila Zahra Syarif', nim: '2023510104', tanggal: '08 Okt 2026', status: 'menunggu', initials: 'NZ', file: 'KTM_2023510104.jpg' },
]

const clone = (data) => data.map((item) => ({ ...item }))
const stores = { bem: clone(bem), hmj: clone(hmj), member: clone(members) }
const wait = () => new Promise((resolve) => setTimeout(resolve, 250))

export async function getVerificationList(type) { await wait(); return stores[type].map((item) => ({ ...item })) }
export async function decideVerification(type, id, status, reason = '') {
  await wait()
  const item = stores[type].find((entry) => entry.id === id)
  if (!item || item.status !== 'menunggu') return { ok: false, error: 'Pengajuan sudah diproses.' }
  item.status = status
  item.alasanPenolakan = status === 'ditolak' ? reason : null
  return { ok: true, data: { ...item } }
}
