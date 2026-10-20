import { useEffect, useState } from 'react'
import { Outlet } from 'react-router-dom'
import { AdminDataProvider } from '../services/adminData.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import PlatformLogo from '../components/PlatformLogo.jsx'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import Sidebar from '../components/Sidebar.jsx'
import '../styles/admin.css'
import '../styles/sidebar.css'
import Footer from '../components/Footer.jsx'

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
          { to: '/superadmin', label: 'Dashboard', icon: 'grid', end: true },
          { to: '/superadmin/pengguna', label: 'Manajemen Administrator', icon: 'users' },
          { to: '/admin-panel/activities', label: 'Log Aktivitas', icon: 'activity' },
        ] : []),
        ...(isAdministrator ? [
          { to: '/admin/verifikasi-bem', label: 'Daftar Registrasi BEM', icon: 'user-check' },
        ] : []),
      ],
    },
    ...(canViewRoles ? [{
      label: 'AKSES & KEAMANAN',
      items: [
        { to: '/admin-panel/roles', label: 'Role', icon: 'shield' },
        { to: '/admin-panel/permission', label: 'Permission', icon: 'sliders' },
      ],
    }] : []),
    ...(isSuperAdmin ? [{
      label: 'AKUN',
      items: [
        { to: '/superadmin/profil', label: 'Profil Saya', icon: 'profile' },
      ],
    }] : []),
  ].filter((group) => group.items.length > 0)
  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [open])
  return <AdminDataProvider><div className="admin-shell"><Sidebar groups={groups} open={open} onClose={() => setOpen(false)} logoTo="/admin" ariaLabel="Navigasi admin" /><div className="admin-main"><header className="admin-topbar"><button className="admin-menu-button" type="button" aria-label="Buka menu" onClick={() => setOpen(true)}><AdminIcon name="menu" /></button><div className="admin-context"><PlatformLogo variant="topbar" /><span>PTMA Jawa Timur&nbsp; / &nbsp;<strong>Pusat Tata Kelola</strong></span></div><div className="admin-top-account"><span className="admin-notification"><AdminIcon name="bell" /><i /></span><span className="admin-top-divider" /><span className="admin-account-copy"><strong>{profileName}</strong><small>{profileSubtitle}</small></span><span className="admin-avatar"><AdminIcon name="profile" size={17} /></span></div></header><Outlet /><Footer variant="content" /></div></div></AdminDataProvider>
}
export default AdminShell
