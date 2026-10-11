const fixtures = {
  menunggu: {
    jenisPendaftar: 'organisasi',
    namaPendaftar: 'HMJ Sistem Informasi',
    tanggalKirim: '2026-10-08',
    status: 'menunggu',
    alasanPenolakan: null,
  },
  disetujui: {
    jenisPendaftar: 'organisasi',
    namaPendaftar: 'HMJ Sistem Informasi',
    tanggalKirim: '2026-10-08',
    status: 'disetujui',
    alasanPenolakan: null,
  },
  ditolak: {
    jenisPendaftar: 'organisasi',
    namaPendaftar: 'HMJ Sistem Informasi',
    tanggalKirim: '2026-10-08',
    status: 'ditolak',
    alasanPenolakan:
      'SK Kepengurusan tidak terbaca. Unggah ulang dokumen yang jelas.',
  },
  'ditolak-anggota': {
    jenisPendaftar: 'anggota',
    namaPendaftar: 'Ahmad Zizou Fakhri',
    tanggalKirim: '2026-10-08',
    status: 'ditolak',
    alasanPenolakan: 'Data NIM tidak sesuai dengan foto KTM.',
  },
}

export function getPreviewKey() {
  if (!import.meta.env.DEV) return null
  const params = new URLSearchParams(window.location.search)
  return params.get('pratinjau')
}

export function getFixture(key) {
  return fixtures[key] ?? null
}

export function simulateResubmit() {
  return new Promise((resolve) => {
    setTimeout(() => resolve({ ok: true, unavailable: false, error: null }), 1200)
  })
}
