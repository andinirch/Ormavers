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

export function sortCategories(list) {
  if (!Array.isArray(list)) return []
  const map = new Map(list.map((item) => [String(item.nama_kategori).trim(), item]))
  const sorted = []
  let lainnya = null

  KATEGORI_ORDER.forEach((name) => {
    if (map.has(name)) {
      const item = map.get(name)
      map.delete(name)
      if (name.toLowerCase() === 'lainnya') {
        lainnya = item
      } else {
        sorted.push(item)
      }
    }
  })

  // Sisa kategori lain jika ada
  for (const remaining of map.values()) {
    if (String(remaining.nama_kategori).toLowerCase() === 'lainnya') {
      lainnya = remaining
    } else {
      sorted.push(remaining)
    }
  }

  // Pastikan "Lainnya" selalu di posisi paling akhir
  if (lainnya) {
    sorted.push(lainnya)
  }

  return sorted
}
