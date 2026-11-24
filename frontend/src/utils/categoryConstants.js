// Daftar resmi kategori event (sama dengan KategoriEventSeeder backend)
export const KATEGORI_ORDER = [
  'Open Recruitment',
  'Kaderisasi',
  'Pelatihan Kepemimpinan',
  'Musyawarah dan Rapat Kerja',
  'Pelantikan dan Serah Terima Jabatan',
  'Seminar',
  'Workshop dan Pelatihan',
  'Kuliah Umum',
  'Diskusi dan Bedah Buku',
  'Lomba dan Kompetisi',
  'Kajian Keislaman',
  'Peringatan Hari Besar Islam',
  'Kegiatan Ramadan',
  'Bakti Sosial dan Pengabdian Masyarakat',
  'Kemanusiaan dan Tanggap Bencana',
  'Seni dan Budaya',
  'Olahraga',
  'Kewirausahaan',
  'Studi Banding dan Kunjungan',
  'Gathering dan Keakraban',
  'Milad dan Dies Natalis',
  'Musyawarah',
  'Forum',
  'Lainnya',
]

// Hanya kategori resmi yang ditampilkan, diurutkan A-Z, "Lainnya" selalu di akhir
export function sortCategories(list) {
  if (!Array.isArray(list)) return []
  const resmi = new Set(KATEGORI_ORDER)
  const nama = (item) => String(item.nama_kategori ?? '').trim()
  const items = list.filter((item) => resmi.has(nama(item)))
  const lainnya = items.filter((item) => nama(item).toLowerCase() === 'lainnya')
  const lainnyaLain = items
    .filter((item) => nama(item).toLowerCase() !== 'lainnya')
    .sort((a, b) => nama(a).localeCompare(nama(b), 'id', { sensitivity: 'base' }))
  return [...lainnyaLain, ...lainnya]
}
