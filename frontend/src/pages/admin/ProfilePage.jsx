import { PageIntro } from './DashboardPage.jsx'
import { useAuth } from '../../context/AuthContext.jsx'

function ProfilePage() {
  const { user, roles } = useAuth()
  const nama = user?.nama || user?.username || 'Pengguna'
  const roleName = roles?.[0] || user?.peran || '—'
  const username = user?.username || '—'
  const statusAkun = user?.status_akun || 'aktif'
  const initials = nama.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() || 'P'

  return (
    <main className="admin-page">
      <PageIntro eyebrow="AKUN • ADMIN PANEL" title="Profil Saya" description="Informasi akun yang sedang digunakan pada Admin Panel." />
      <section className="admin-panel admin-profile-card">
        <div className="admin-profile-avatar">{initials}</div>
        <div className="admin-profile-details">
          <div><span>Nama</span><strong>{nama}</strong></div>
          <div><span>Username</span><strong>{username}</strong></div>
          <div><span>Role</span><strong style={{ textTransform: 'capitalize' }}>{String(roleName).replace(/_/g, ' ')}</strong></div>
          <div><span>Status Akun</span><strong style={{ textTransform: 'capitalize' }}>{statusAkun}</strong></div>
        </div>
        <p className="admin-profile-note">Data profil bersifat read-only. Perubahan data diri, kata sandi, dan foto profil belum didukung oleh backend API.</p>
      </section>
    </main>
  )
}
export default ProfilePage
