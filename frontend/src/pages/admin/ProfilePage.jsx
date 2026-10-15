import { PageIntro } from './DashboardPage.jsx'

function ProfilePage() {
  return <main className="admin-page"><PageIntro eyebrow="AKUN • SUPER ADMIN" title="Profil Saya" description="Informasi akun yang sedang digunakan pada Admin Panel." /><section className="admin-panel admin-profile-card"><div className="admin-profile-avatar">SA</div><div className="admin-profile-details"><div><span>Nama</span><strong>Super Admin</strong></div><div><span>Role</span><strong>super admin</strong></div><div><span>Email</span><strong className="admin-text-subtle">Belum tersedia dari integrasi autentikasi</strong></div></div><p className="admin-profile-note">Data profil dan perubahan akun belum terhubung ke backend atau sesi autentikasi.</p></section></main>
}
export default ProfilePage
