import { useAuth } from '../../context/AuthContext.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import '../../styles/profile.css'

function formatRole(value) {
  const text = String(value ?? '').replace(/_/g, ' ').trim()
  if (!text) return '—'
  return text.charAt(0).toUpperCase() + text.slice(1)
}

function ProfilePage() {
  const { user, roles } = useAuth()
  const nama = user?.nama || user?.username || 'Pengguna'
  const username = user?.username || '—'
  const roleName = formatRole(roles?.[0]?.name ?? roles?.[0] ?? user?.peran)
  const initials = nama.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() || 'A'

  const rows = [
    { label: 'Nama', value: nama },
    { label: 'Username', value: username },
    { label: 'Role', value: roleName },
  ]

  return (
    <main className="admin-page admin-profile-page">
      <PageHeader
        title="Profil Saya"
        subtitle="Informasi akun yang sedang digunakan pada Admin Panel."
        breadcrumb={['Admin Panel', 'Profil Saya']}
        icon="profile-bold"
      />

      <section className="profile-card admin-profile-card">
        <div className="admin-profile-hero">
          <div className="profile-avatar" aria-hidden="true">{initials}</div>
          <div className="admin-profile-names">
            <strong className="admin-profile-username">{nama}</strong>
            <span className="admin-profile-handle">{username}</span>
          </div>
        </div>

        <div className="admin-profile-info">
          {rows.map((row) => (
            <div className="admin-profile-row" key={row.label}>
              <span className="admin-profile-label">{row.label}</span>
              <span className="admin-profile-value">{row.value}</span>
            </div>
          ))}
        </div>
      </section>
    </main>
  )
}

export default ProfilePage
