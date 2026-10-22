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
    namaDokumenDitolak: 'SK Kepengurusan',
    alasanPenolakan:
      'SK Kepengurusan tidak terbaca. Unggah ulang dokumen yang jelas.',
  },
  'ditolak-sk-organisasi': {
    jenisPendaftar: 'organisasi',
    namaPendaftar: 'HMJ Sistem Informasi',
    tanggalKirim: '2026-10-08',
    status: 'ditolak',
    namaDokumenDitolak: 'SK Organisasi',
    alasanPenolakan:
      'SK Organisasi tidak sesuai. Unggah ulang dokumen yang benar.',
  },
  'ditolak-gagal': {
    jenisPendaftar: 'organisasi',
    namaPendaftar: 'HMJ Sistem Informasi',
    tanggalKirim: '2026-10-08',
    status: 'ditolak',
    namaDokumenDitolak: 'SK Kepengurusan',
    alasanPenolakan:
      'Dokumen sebelumnya tidak terbaca. Unggah ulang dokumen yang jelas.',
  },
  'ditolak-anggota': {
    jenisPendaftar: 'anggota',
    namaPendaftar: 'Ahmad Zizou Fakhri',
    tanggalKirim: '2026-10-08',
    status: 'ditolak',
    namaDokumenDitolak: null,
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

export function simulateResubmit(shouldFail = false) {
  return new Promise((resolve) => {
    setTimeout(() => resolve(shouldFail
      ? { ok: false, unavailable: false, error: 'Dokumen gagal dikirim. Silakan coba lagi.' }
      : { ok: true, unavailable: false, error: null }), 1200)
  })
}
