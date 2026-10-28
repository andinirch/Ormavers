import { useEffect, useRef, useState } from 'react'
import { Outlet } from 'react-router-dom'
import { AdminDataProvider } from '../services/adminData.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import PlatformLogo from '../components/PlatformLogo.jsx'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import Sidebar from '../components/Sidebar.jsx'
import '../styles/admin.css'
import '../styles/sidebar.css'
import Footer from '../components/Footer.jsx'
import NotificationPanel from '../components/notifications/NotificationPanel.jsx'
import useNotifications from '../components/notifications/useNotifications.js'

function AdminShell() {
  const [open, setOpen] = useState(false)
  const [notifOpen, setNotifOpen] = useState(false)
  const bellRef = useRef(null)
  const notifications = useNotifications()
  const { user, roles, permissions } = useAuth()
  const sessionRole = roles[0]?.name ?? roles[0] ?? user?.peran
  const profileSubtitle = sessionRole === 'super_admin' ? 'Super Admin' : sessionRole === 'administrator' ? 'Administrator' : sessionRole === 'akun_hmj_ukm' ? 'Akun Organisasi HMJ/UKM' : 'Akun Organisasi BEM'
  const profileName = user?.username ?? user?.nama ?? (sessionRole === 'super_admin' ? 'Super Admin' : sessionRole === 'administrator' ? 'Administrator' : 'Akun Organisasi')
  const isSuperAdmin = user?.peran === 'super_admin'
  const isAdministrator = user?.peran === 'administrator'
  const can = (permission) => isSuperAdmin || permissions?.includes(permission)
  const canViewRoles = can('view roles')

  const logoTo = isSuperAdmin ? '/superadmin' : isAdministrator ? '/admin/verifikasi-bem' : '/admin'

  const groups = [
    {
      label: 'MENU UTAMA',
      items: [
        ...(isSuperAdmin ? [
          { to: '/superadmin', label: 'Dashboard', icon: 'grid', end: true },
          { to: '/superadmin/pengguna', label: 'Manajemen Pengguna', icon: 'users' },
           { to: '/admin-panel/activities', label: 'Log Aktivitas', icon: 'activity' },
         ] : []),
         ...(isAdministrator ? [
           { to: '/admin/verifikasi-bem', label: 'Daftar Registrasi BEM', icon: 'user-check' },
           { to: isSuperAdmin ? '/superadmin/direktori' : '/admin/direktori', label: 'Direktori Organisasi', icon: 'compass' },
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
    ...(isAdministrator ? [{
      label: 'AKUN',
      items: [
        { to: '/admin/profil', label: 'Profil Saya', icon: 'profile' },
      ],
    }] : []),
  ].filter((group) => group.items.length > 0)
  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [open])
  return <AdminDataProvider><div className="admin-shell"><Sidebar groups={groups} open={open} onClose={() => setOpen(false)} logoTo={logoTo} ariaLabel="Navigasi admin" /><div className="admin-main"><header className="admin-topbar"><button className="admin-menu-button" type="button" aria-label="Buka menu" onClick={() => setOpen(true)}><AdminIcon name="menu" /></button><div className="admin-context"><PlatformLogo variant="topbar" /><span>PTMA Jawa Timur&nbsp; / &nbsp;<strong>Pusat Tata Kelola</strong></span></div><div className="admin-top-account"><button ref={bellRef} type="button" className="admin-notification notification-bell" aria-label="Buka notifikasi" aria-haspopup="dialog" onClick={() => setNotifOpen(true)}><AdminIcon name="bell" />{notifications.unread > 0 && <i />}</button><span className="admin-top-divider" /><span className="admin-account-copy"><strong>{profileName}</strong><small>{profileSubtitle}</small></span><span className="admin-avatar"><AdminIcon name="profile-bold" size={20} strokeWidth={2.2} /></span></div></header><Outlet /><Footer variant="content" /></div><NotificationPanel open={notifOpen} onClose={() => setNotifOpen(false)} items={notifications.items} unread={notifications.unread} onReadAll={notifications.markAllRead} returnFocusRef={bellRef} /></div></AdminDataProvider>
}
export default AdminShell
