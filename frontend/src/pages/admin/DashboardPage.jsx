import { Link } from 'react-router-dom'
import { useAdminData } from '../../services/useAdminData.jsx'

function DashboardPage() {
  const { users, roles } = useAdminData()
  const permissionCount = new Set(roles.flatMap((role) => role.permissions.split(',').map((permission) => permission.trim()))).size
  return <main className="admin-page"><PageIntro eyebrow="PUSAT KONTROL • SUPER ADMIN" title="Dashboard" description="Ringkasan pengguna, akses, dan keamanan seluruh ekosistem Ormavers." /><div className="admin-summary-grid admin-summary-grid--four"><Summary label="Total Pengguna" value={users.length} tone="blue" /><Summary label="Administrator Aktif" value="—" tone="purple" note="Data autentikasi belum tersedia" /><Summary label="Role Terdefinisi" value={roles.length} tone="teal" /><Summary label="Permission" value={permissionCount} tone="blue" /></div><section className="admin-panel"><div className="admin-panel-heading"><div><h2>Aktivitas Terbaru</h2><p>Audit jejak operasional institusi</p></div><Link to="/admin/aktivitas">Lihat semua log →</Link></div><div className="admin-empty admin-empty--panel"><strong>Log aktivitas belum tersedia</strong><span>Integrasi activity log belum tersedia pada frontend ini.</span></div></section></main>
}
function Summary({ label, value, tone, note }) { return <article className={`admin-summary admin-summary--${tone}`}><span>{label}</span><strong>{value}</strong><small>{note || 'Data Admin Panel lokal'}</small></article> }
export function PageIntro({ eyebrow, title, description, actions }) { return <header className="admin-page-intro"><div><span className="admin-eyebrow">● {eyebrow}</span><h1>{title}</h1><p>{description}</p></div>{actions && <div className="admin-page-actions">{actions}</div>}</header> }
export default DashboardPage
