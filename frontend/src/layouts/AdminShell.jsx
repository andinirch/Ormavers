import { useEffect, useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { AdminDataProvider } from '../services/adminData.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import Logo from '../components/admin/Logo.jsx'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import '../styles/admin.css'

const groups = [
{ label: 'MENU UTAMA', items: [['/admin','Dashboard', 'grid', true], ['/admin/pengguna', 'Manajemen User', 'users'], ['/admin/aktivitas', 'Log Aktivitas', 'activity']] },
  { label: 'AKSES & KEAMANAN', items: [['/admin/roles', 'Role', 'shield'], ['/admin/permission', 'Permission', 'sliders']] },
  { label: 'AKUN', items: [['/admin/profil', 'Profil Saya', 'profile']] },
]
function AdminShell() {
  const [open, setOpen] = useState(false)
  const navigate = useNavigate()
  const { user, roles, logout } = useAuth()
  const handleLogout = async () => {
    setOpen(false)
    await logout()
    navigate('/login', { replace: true })
  }
  const roleLabel = roles[0]?.name ?? roles[0] ?? 'Pengguna'
  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [open])
  return <AdminDataProvider><div className="admin-shell"><aside className={`admin-sidebar${open ? ' is-open' : ''}`}><NavLink to="/admin" onClick={() => setOpen(false)} className="admin-logo"><Logo variant="sidebar" /><span className="admin-logo-copy"><strong>Ormavers</strong><small>{roleLabel}</small></span></NavLink><nav className="admin-sidebar-nav" aria-label="Navigasi admin">{groups.map((group) => <div key={group.label}><p>{group.label}</p>{group.items.map(([to, label, icon, end]) => <NavLink key={to} to={to} end={end} onClick={() => setOpen(false)} className={({ isActive }) => `admin-sidebar-link${isActive ? ' is-active' : ''}`}><AdminIcon name={icon} />{label}</NavLink>)}</div>)}</nav><button className="admin-logout" type="button" onClick={handleLogout}><AdminIcon name="logout" /><span>Keluar</span></button></aside>{open && <button className="admin-drawer-overlay" type="button" aria-label="Tutup menu" onClick={() => setOpen(false)} /> }<div className="admin-main"><header className="admin-topbar"><button className="admin-menu-button" type="button" aria-label="Buka menu" onClick={() => setOpen(true)}><AdminIcon name="menu" /></button><div className="admin-context"><Logo variant="topbar" /><span>PTMA Jawa Timur&nbsp; / &nbsp;<strong>Pusat Tata Kelola</strong></span></div><div className="admin-top-account"><span className="admin-notification"><AdminIcon name="bell" /><i /></span><span className="admin-top-divider" /><span className="admin-account-copy"><strong>{user?.nama ?? user?.username ?? 'Pengguna'}</strong><small>{roleLabel}</small></span><span className="admin-avatar"><AdminIcon name="profile" size={17} /></span></div></header><Outlet /><footer className="admin-footer">© 2026 Ormavers — Platform Kolaborasi Ormawa Muhammadiyah Jawa Timur.</footer></div></div></AdminDataProvider>
}
export default AdminShell
