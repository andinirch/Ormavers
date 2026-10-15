export const ADMIN_USE_MOCKS = true

export const mockDashboard = {
  totalUsers: 1248,
  activeAdministrators: 11,
  definedRoles: 5,
  permissions: 11,
  activities: [
    { initials: 'SA', text: 'Mengubah permission role hmj/ukm: menambahkan', time: '10:05 WIB' },
    { initials: 'SA', text: 'Menangguhkan akun Hendra Kurniawan (Anggota)', time: 'Kemarin' },
    { initials: 'BH', text: 'Menyetujui pendaftaran BEM Fakultas Teknik Universitas', time: '10 Okt' },
    { initials: 'SA', text: 'Membuat akun Administrator baru: Siti Rahmawati', time: '10 Okt' },
  ],
}

export const mockUsers = [
  { id: 'mock-1', name: 'Super Admin', email: 'superadmin@ormavers.id', roleId: 'role-super-admin', organizationId: null, status: 'Aktif', lastLoginAt: '14 Okt 2026 10:05 WIB', initials: 'SA' },
  { id: 'mock-2', name: 'Budi Hartono', email: 'budi.hartono@ormavers.id', roleId: 'role-administrator', organizationId: 'org-pusat', status: 'Aktif', lastLoginAt: '10 Okt 2026 14:25 WIB', initials: 'BH' },
  { id: 'mock-4', name: 'Faisal Akbar', email: 'faisal.akbar@ormavers.id', roleId: 'role-administrator', organizationId: 'org-pusat', status: 'Nonaktif', lastLoginAt: '02 Okt 2026 08:10 WIB', initials: 'FA' },
  { id: 'mock-5', name: 'Dewi Lestari', email: 'dewi.lestari@mail.com', roleId: 'role-bem', organizationId: 'BEM Universitas Muhammadiyah Malang', status: 'Aktif', lastLoginAt: '13 Okt 2026 16:48 WIB', initials: 'DL' },
  { id: 'mock-7', name: 'Nabila Putri', email: 'nabila.putri@mail.com', roleId: 'role-hmj', organizationId: 'HMJ Teknik Mesin', status: 'Menunggu', lastLoginAt: '', initials: 'NP' },
  { id: 'mock-8', name: 'Hendra Kurniawan', email: 'hendra.k@mail.com', roleId: 'role-anggota', organizationId: 'UKM Jurnalistik', status: 'Ditangguhkan', lastLoginAt: '12 Okt 2026 21:14 WIB', initials: 'HK' },
]

export const mockActivities = [
  { id: 1, date: '14 Okt 2026', time: '10:05 WIB', initials: 'SA', user: 'Super Admin', role: 'Root Privileges', type: 'Role', text: 'Mengubah permission role hmj/ukm: menambahkan', code: 'verify members' },
  { id: 2, date: '13 Okt 2026', time: '16:20 WIB', initials: 'SA', user: 'Super Admin', role: 'Root Privileges', type: 'Akun', text: 'Menangguhkan akun Anggota: Hendra Kurniawan', code: 'hendra.k@mail.com' },
  { id: 3, date: '12 Okt 2026', time: '09:30 WIB', initials: 'SA', user: 'Super Admin', role: 'Root Privileges', type: 'Role', text: 'Mengubah permission role bem: menambahkan', code: 'verify members' },
  { id: 4, date: '10 Okt 2026', time: '14:25 WIB', initials: 'BH', user: 'Budi Hartono', role: 'Administrator', type: 'Verifikasi', text: 'Menyetujui pendaftaran BEM Fakultas Teknik Universitas Muhammadiyah Surabaya', code: '' },
  { id: 5, date: '10 Okt 2026', time: '11:10 WIB', initials: 'SA', user: 'Super Admin', role: 'Root Privileges', type: 'Akun', text: 'Membuat akun Administrator baru: Siti Rahmawati', code: 'siti.rahmawati@ormavers.id' },
  { id: 6, date: '10 Okt 2026', time: '09:42 WIB', initials: 'SR', user: 'Siti Rahmawati', role: 'Administrator', type: 'Login', text: 'Berhasil masuk ke dalam sistem dari IP', code: '103.28.12.89' },
  { id: 7, date: '09 Okt 2026', time: '16:50 WIB', initials: 'BH', user: 'Budi Hartono', role: 'Administrator', type: 'Verifikasi', text: 'Menolak pendaftaran HMJ Teknik Mesin dengan alasan: Dokumen SK tidak lengkap', code: '' },
  { id: 8, date: '09 Okt 2026', time: '13:15 WIB', initials: 'SA', user: 'Super Admin', role: 'Root Privileges', type: 'Akun', text: 'Menonaktifkan akun Administrator: Faisal Akbar', code: 'faisal.akbar@ormavers.id' },
]

export const mockPermissionModules = {
  'Pengguna & Role': ['view users', 'create users', 'edit users', 'delete users', 'view roles'],
  Verifikasi: ['verify bem', 'verify hmj/ukm', 'verify members'],
  'Profil & Keanggotaan': ['view profile', 'view registration status', 'view members'],
}
