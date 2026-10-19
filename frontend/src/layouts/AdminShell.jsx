import { useEffect, useState } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { AdminDataProvider } from '../services/adminData.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import PlatformLogo from '../components/PlatformLogo.jsx'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import '../styles/admin.css'
import Footer from '../components/Footer.jsx'
import LogoutButton from '../components/LogoutButton.jsx'

function AdminShell() {
  const [open, setOpen] = useState(false)
  const { user, roles, permissions } = useAuth()
  const sessionRole = roles[0]?.name ?? roles[0] ?? user?.peran
  const profileSubtitle = sessionRole === 'super_admin' ? 'Super Admin' : sessionRole === 'administrator' ? 'Administrator' : sessionRole === 'akun_hmj_ukm' ? 'Akun Organisasi HMJ/UKM' : 'Akun Organisasi BEM'
  const profileName = sessionRole === 'super_admin' ? 'Super Admin' : sessionRole === 'administrator' ? 'Administrator' : user?.nama ?? 'Akun Organisasi'
  const isSuperAdmin = user?.peran === 'super_admin'
  const isAdministrator = user?.peran === 'administrator'
  const canViewRoles = permissions?.includes('view roles') || isSuperAdmin

  const groups = [
    {
      label: 'MENU UTAMA',
       items: [
         ...(isSuperAdmin ? [
           ['/superadmin', 'Dashboard', 'grid', true],
           ['/superadmin/pengguna', 'Manajemen Administrator', 'users'],
           ['/admin-panel/activities', 'Log Aktivitas', 'activity'],
         ] : []),
         ...(isAdministrator ? [
           ['/admin/verifikasi-bem', 'Daftar Registrasi BEM', 'user-check'],
         ] : []),

      ],
    },
    ...(canViewRoles ? [{
      label: 'AKSES & KEAMANAN',
      items: [
        ['/admin-panel/roles', 'Role', 'shield'],
        ['/admin-panel/permission', 'Permission', 'sliders'],
      ],
    }] : []),
     ...(isSuperAdmin ? [{
       label: 'AKUN',
       items: [
         ['/superadmin/profil', 'Profil Saya', 'profile'],
       ],
     }] : []),
  ].filter((group) => group.items.length > 0)
  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [open])
  return <AdminDataProvider><div className="admin-shell"><aside className={`admin-sidebar${open ? ' is-open' : ''}`}><NavLink to="/admin" onClick={() => setOpen(false)} className="admin-logo"><PlatformLogo variant="sidebar" /></NavLink><nav className="admin-sidebar-nav" aria-label="Navigasi admin">{groups.map((group) => <div key={group.label}><p>{group.label}</p>{group.items.map(([to, label, icon, end]) => <NavLink key={to} to={to} end={end} onClick={() => setOpen(false)} className={({ isActive }) => `admin-sidebar-link${isActive ? ' is-active' : ''}`}><AdminIcon name={icon} />{label}</NavLink>)}</div>)}</nav><LogoutButton /></aside>{open && <button className="admin-drawer-overlay" type="button" aria-label="Tutup menu" onClick={() => setOpen(false)} /> }<div className="admin-main"><header className="admin-topbar"><button className="admin-menu-button" type="button" aria-label="Buka menu" onClick={() => setOpen(true)}><AdminIcon name="menu" /></button><div className="admin-context"><PlatformLogo variant="topbar" /><span>PTMA Jawa Timur&nbsp; / &nbsp;<strong>Pusat Tata Kelola</strong></span></div><div className="admin-top-account"><span className="admin-notification"><AdminIcon name="bell" /><i /></span><span className="admin-top-divider" /><span className="admin-account-copy"><strong>{profileName}</strong><small>{profileSubtitle}</small></span><span className="admin-avatar"><AdminIcon name="profile" size={17} /></span></div></header><Outlet /><Footer variant="content" /></div></div></AdminDataProvider>
}
export default AdminShell
