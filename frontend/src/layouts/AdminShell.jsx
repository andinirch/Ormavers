import { NavLink, Outlet } from 'react-router-dom'
import { AdminDataProvider } from '../services/adminData.jsx'
import '../styles/admin.css'

function AdminShell() {
  return <AdminDataProvider><div className="admin-shell"><aside className="admin-sidebar"><NavLink to="/admin" className="admin-logo"><span className="admin-logo-mark">⌘</span><span><strong>Ormavers</strong><small>SUPER ADMIN</small></span></NavLink><nav className="admin-sidebar-nav" aria-label="Navigasi admin"><p>MENU UTAMA</p><NavItem to="/admin" end icon="▦">Dashboard</NavItem><NavItem to="/admin/pengguna" icon="♧">Manajemen User</NavItem><NavItem to="/admin/aktivitas" icon="◌">Log Aktivitas</NavItem><p>AKSES & KEAMANAN</p><NavItem to="/admin/roles" icon="♙">Role</NavItem><NavItem to="/admin/permission" icon="☷">Permission</NavItem><p>AKUN</p><NavItem to="/admin/profil" icon="◎">Profil Saya</NavItem></nav><button className="admin-logout" type="button">↪ <span>Keluar</span></button></aside><div className="admin-main"><header className="admin-topbar"><div className="admin-context"><span className="admin-context-mark">⌘</span><span>PTMA Jawa Timur&nbsp; / &nbsp;<strong>Pusat Tata Kelola</strong></span></div><div className="admin-top-account"><span>♧</span><span><strong>Super Admin</strong><small>Super Admin</small></span><b>♙</b></div></header><Outlet /></div></div></AdminDataProvider>
}
function NavItem({ children, icon, ...props }) { return <NavLink {...props} className={({ isActive }) => `admin-sidebar-link${isActive ? ' is-active' : ''}`}><span>{icon}</span>{children}</NavLink> }
export default AdminShell
