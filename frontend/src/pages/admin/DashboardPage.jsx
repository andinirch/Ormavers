import { Link } from 'react-router-dom'
import { mockDashboard, ADMIN_USE_MOCKS } from '../../mocks/adminMockData.js'
import { useAdminData } from '../../services/useAdminData.jsx'

export function PageIntro({ eyebrow, title, description, actions, status }) { return <header className="admin-page-intro"><div><span className="admin-eyebrow">● {eyebrow}</span><h1>{title}</h1><p>{description}</p></div>{status || actions ? <div className="admin-page-intro-side">{status}{actions}</div> : null}</header> }
function Summary({ label, value, tone = 'blue' }) { return <article className={`admin-summary admin-summary--${tone}`}><div><span>{label}</span><strong>{value}</strong></div></article> }
function DashboardPage() {
  const { users, roles, permissions } = useAdminData()
  const activeAdminCount = users.filter((u) => u.role === 'administrator' && u.status === 'aktif').length
  const data = ADMIN_USE_MOCKS
    ? mockDashboard
    : {
        totalUsers: users.length,
        activeAdministrators: activeAdminCount,
        definedRoles: roles.length,
        permissions: permissions?.length || 0,
        activities: [],
      }

  return (
    <main className="admin-page admin-dashboard-page">
      <PageIntro eyebrow="PUSAT KONTROL • SUPER ADMIN" title="Dashboard" description="Ringkasan pengguna, akses, dan keamanan seluruh ekosistem Ormavers." />
      <div className="admin-summary-grid admin-summary-grid--four">
        <Summary label="Total Pengguna" value={data.totalUsers.toLocaleString('id-ID')} />
        <Summary label="Administrator Aktif" value={data.activeAdministrators.toLocaleString('id-ID')} tone="purple" />
        <Summary label="Role Terdefinisi" value={data.definedRoles.toLocaleString('id-ID')} tone="teal" />
        <Summary label="Permission" value={data.permissions.toLocaleString('id-ID')} />
      </div>
      <section className="admin-panel">
        <div className="admin-panel-heading">
          <div>
            <h2>Aktivitas Terbaru</h2>
            <p>Audit jejak operasional institusi</p>
          </div>
          <Link to="/admin/aktivitas">Lihat semua log →</Link>
        </div>
        {data.activities.length ? (
          <div className="admin-activity-preview">
            {data.activities.map((activity) => (
              <div className="admin-activity-row" key={`${activity.initials}-${activity.time}`}>
                <span className="admin-avatar admin-avatar--soft">{activity.initials}</span>
                <span>{activity.text}</span>
                <span className="admin-activity-time">{activity.time}</span>
              </div>
            ))}
          </div>
        ) : (
          <div className="admin-empty">
            <strong>Log aktivitas belum tersedia</strong>
            <span>Gunakan menu Log Aktivitas untuk melihat jejak audit sistem.</span>
          </div>
        )}
      </section>
    </main>
  )
}
export default DashboardPage
