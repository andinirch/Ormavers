import { PageIntro } from './DashboardPage.jsx'
import { useAdminData } from '../../services/useAdminData.jsx'

function PermissionsPage() {
  const { roles } = useAdminData()
  const permissions = [...new Set(roles.flatMap((role) => role.permissions.split(',').map((permission) => permission.trim())))].filter(Boolean)
  const usersByPermission = (permission) => roles.filter((role) => role.permissions.split(',').map((item) => item.trim()).includes(permission)).map((role) => role.name)
  return <main className="admin-page"><PageIntro eyebrow="AKSES & KEAMANAN • SUPER ADMIN" title="Permission" description="Daftar hak akses sistem dan role yang menggunakannya." /><section className="admin-panel admin-permission-panel"><div className="admin-info-banner">ⓘ Permission bersifat tetap dari sistem. Atur penggunaannya melalui halaman <strong>Role</strong>.</div><div className="admin-table-wrap"><table><thead><tr><th>#</th><th>Permission</th><th>Digunakan oleh role</th></tr></thead><tbody>{permissions.length ? permissions.map((permission, index) => <tr key={permission}><td>{index + 1}</td><td><code className="admin-code">{permission}</code></td><td><div className="admin-chip-list">{usersByPermission(permission).map((role) => <span className="admin-chip" key={role}>{role}</span>)}</div></td></tr>) : <tr><td colSpan="3" className="admin-empty">Belum ada permission tersedia.</td></tr>}</tbody></table></div><footer className="admin-panel-footer">🔒 Akses permission bersifat read-only pada halaman ini.</footer></section></main>
}
export default PermissionsPage
