import { Link } from 'react-router-dom'
import { useAdminData } from '../../services/useAdminData.jsx'

function DashboardPage() {
  const { users, roles, organizations } = useAdminData()
  return <><section className="admin-page-heading"><h1>Dashboard</h1></section><main className="admin-content"><div className="admin-summary-grid"><Summary label="Total pengguna" value={users.length} /><Summary label="Total role" value={roles.length} /><Summary label="Organisasi terdaftar" value={organizations.length} /></div><section className="admin-welcome"><h2>Selamat datang, Super Admin</h2><div className="admin-shortcuts"><Link to="/admin/roles">Kelola Roles <span>→</span></Link><Link to="/admin/pengguna">Kelola Pengguna <span>→</span></Link></div></section></main></>
}
function Summary({ label, value }) { return <article className="admin-summary"><span>{label}</span><strong>{String(value).padStart(3, '0')}</strong></article> }
export default DashboardPage
