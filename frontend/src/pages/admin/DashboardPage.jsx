import { Link } from 'react-router-dom'
import { useEffect, useState } from 'react'
import { getSummary } from '../../api/adminPanel.js'
import { useAdminData } from '../../services/useAdminData.jsx'

export function PageIntro({ eyebrow, title, description, actions, status }) { return <header className="admin-page-intro"><div><span className="admin-eyebrow">● {eyebrow}</span><h1>{title}</h1><p>{description}</p></div>{status || actions ? <div className="admin-page-intro-side">{status}{actions}</div> : null}</header> }
function Summary({ label, value, tone = 'blue' }) { return <article className={`admin-summary admin-summary--${tone}`}><div><span>{label}</span><strong>{value}</strong></div></article> }
function DashboardPage() {
  const { users, roles, permissions } = useAdminData()
  const [summary, setSummary] = useState(null)
  const activeAdminCount = users.filter((u) => u.role === 'administrator' && u.status === 'aktif').length

  useEffect(() => {
    getSummary().then((result) => setSummary(result?.data ?? result)).catch(() => setSummary(null))
  }, [])

  const data = summary ?? {
    total_pengguna: users.length,
    administrator_aktif: activeAdminCount,
    role_terdefinisi: roles.length,
    permission: permissions?.length || 0,
    aktivitas_terbaru: [],
  }

  return (
    <main className="admin-page admin-dashboard-page">
      <PageIntro eyebrow="PUSAT KONTROL • SUPER ADMIN" title="Dashboard" description="Ringkasan pengguna, akses, dan keamanan seluruh ekosistem Ormavers." />
      <div className="admin-summary-grid admin-summary-grid--four">
        <Summary label="Total Pengguna" value={data.total_pengguna.toLocaleString('id-ID')} />
        <Summary label="Administrator Aktif" value={data.administrator_aktif.toLocaleString('id-ID')} tone="purple" />
        <Summary label="Role Terdefinisi" value={data.role_terdefinisi.toLocaleString('id-ID')} tone="teal" />
        <Summary label="Permission" value={data.permission.toLocaleString('id-ID')} />
      </div>
      <section className="admin-panel">
        <div className="admin-panel-heading">
          <div>
            <h2>Aktivitas Terbaru</h2>
            <p>Audit jejak operasional institusi</p>
          </div>
          <Link to="/admin-panel/activities">Lihat semua log →</Link>
        </div>
{data.aktivitas_terbaru.length ? (
           <div className="admin-activity-preview">
             {data.aktivitas_terbaru.map((activity) => (
               <div className="admin-activity-row" key={`${activity.nama_pengguna}-${activity.waktu_aktivitas}`}>
                 <span className="admin-avatar admin-avatar--soft">{activity.nama_pengguna?.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase()}</span>
                 <span>{activity.keterangan}</span>
                 <span className="admin-activity-time">{activity.waktu_aktivitas}</span>
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
