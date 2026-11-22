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
           { to: isSuperAdmin ? '/superadmin/direktori' : '/admin/direktori', label: 'Direktori Organisasi', icon: 'university' },
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

  const currentDateFormatted = new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })

  return <AdminDataProvider><div className="admin-shell"><Sidebar groups={groups} open={open} onClose={() => setOpen(false)} logoTo={logoTo} ariaLabel="Navigasi admin" /><div className="admin-main"><header className="admin-topbar app-topbar"><button className="admin-menu-button" type="button" aria-label="Buka menu" onClick={() => setOpen(true)}><AdminIcon name="menu" /></button><div className="topbar-date-pill app-topbar__date-pill"><AdminIcon name="calendar" size={14} /><span>{currentDateFormatted}</span></div><div className="admin-top-account topbar-actions app-topbar__actions"><button ref={bellRef} type="button" className="admin-notification notification-bell" aria-label="Buka notifikasi" aria-haspopup="dialog" onClick={() => setNotifOpen(true)}><AdminIcon name="bell" />{notifications.unread > 0 && <i />}</button><span className="topbar-divider" aria-hidden="true" /><div className="topbar-profile-capsule"><span className="admin-avatar"><AdminIcon name="profile-bold" size={20} strokeWidth={2.2} /></span><span className="admin-account-copy topbar-profile-info"><strong>{profileName}</strong><small>{profileSubtitle}</small></span></div></div></header><div className="admin-content"><Outlet /></div><Footer variant="content" /></div><NotificationPanel open={notifOpen} onClose={() => setNotifOpen(false)} items={notifications.items} unread={notifications.unread} loading={notifications.loading} error={notifications.error} onReadAll={notifications.markAllRead} onItemClick={(item) => { notifications.markRead(item); setNotifOpen(false) }} returnFocusRef={bellRef} /></div></AdminDataProvider>
}
export default AdminShell
