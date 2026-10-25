import { useEffect, useState } from 'react'
import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import { getMyRegistrationStatus } from '../services/registrationStatusApi.js'
import { verificationContexts } from '../services/verification/verificationApi.js'
import '../styles/verification.css'
import '../styles/sidebar.css'
import '../styles/profile.css'
import Footer from '../components/Footer.jsx'
import OrmaLogo from '../components/OrmaLogo.jsx'
import Sidebar from '../components/Sidebar.jsx'

const navigationByRole = {
  Administrator: [
    { to: '/verifikasi/administrator', label: 'Daftar Registrasi BEM', icon: 'bem' },
    { to: '/verifikasi/direktori', label: 'Direktori Organisasi', icon: 'grid', permission: 'view profile' },
  ],
  'Akun Organisasi BEM': [
    { to: '/verifikasi/direktori', label: 'Direktori Organisasi', icon: 'grid', permission: 'view profile' },
    { to: '/verifikasi/bem?bagian=hmj', label: 'Daftar Registrasi HMJ/UKM', icon: 'hmj', permission: 'verify hmj/ukm' },
    { to: '/verifikasi/bem?bagian=anggota', label: 'Daftar Permintaan Anggota', icon: 'member', permission: 'verify members' },
    { to: '/verifikasi/profil', label: 'Profil Saya', icon: 'profile', permission: 'view profile' },
  ],
  'Akun Organisasi HMJ/UKM': [
    { to: '/verifikasi/direktori', label: 'Direktori Organisasi', icon: 'grid', permission: 'view profile' },
    { to: '/verifikasi/hmj', label: 'Daftar Permintaan Anggota', icon: 'member', permission: 'verify members' },
    { to: '/verifikasi/profil-hmj', label: 'Profil Saya', icon: 'profile', permission: 'view profile' },
  ],
  Anggota: [
    { to: '/direktori', label: 'Direktori Organisasi', icon: 'grid', permission: 'view profile' },
    { to: '/profil', label: 'Profil Saya', icon: 'profile', permission: 'view profile' },
  ],
}

function Icon({ name, size = 20 }) {
  const paths = {
    bem: <><path d="M4 18v-1.5A3.5 3.5 0 0 1 7.5 13h3A3.5 3.5 0 0 1 14 16.5V18" /><circle cx="9" cy="7" r="3" /><path d="m15 11 1.5 1.5L20 9" /></>,
    hmj: <><circle cx="5" cy="5" r="1.3" /><circle cx="12" cy="5" r="1.3" /><circle cx="19" cy="5" r="1.3" /><circle cx="5" cy="12" r="1.3" /><circle cx="12" cy="12" r="1.3" /><circle cx="19" cy="12" r="1.3" /><circle cx="5" cy="19" r="1.3" /><circle cx="12" cy="19" r="1.3" /></>,
    member: <><circle cx="9" cy="8" r="3" /><path d="M3 19a6 6 0 0 1 12 0M18 9v6M15 12h6" /></>,
    profile: <><circle cx="12" cy="12" r="9" /><circle cx="12" cy="9" r="2.5" /><path d="M7.5 18a5 5 0 0 1 9 0" /></>,
    logout: <><path d="M10 17H5V7h5M14 8l4 4-4 4M18 12H9" /></>,
    shield: <><path d="M12 3 19 6v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z" /><path d="m9 12 2 2 4-4" /></>,
  }
  return <svg className="verification-icon" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>
}

function VerificationShell() {
  const { pathname, search } = useLocation()
  const { user, roles, permissions } = useAuth()
  const roleNames = roles.map((role) => typeof role === 'string' ? role : role?.name).filter(Boolean)
  const sessionRole = roleNames.includes('akun_hmj_ukm')
    ? 'akun_hmj_ukm'
    : roleNames.includes('akun_bem')
      ? 'akun_bem'
      : user?.peran || roleNames[0]
  const contextKey = sessionRole === 'administrator'
    ? 'administrator'
    : sessionRole === 'akun_hmj_ukm'
      ? 'hmj'
      : sessionRole === 'anggota' || sessionRole === 'member'
        ? 'anggota'
        : 'bem'
  const activeContext = verificationContexts[contextKey]
  const isSuperAdmin = roleNames.includes('super_admin') || user?.peran === 'super_admin'
  const canUsePermission = (permission) => isSuperAdmin || !permission || permissions.includes(permission)
  const [organizationName, setOrganizationName] = useState(null)

  useEffect(() => {
    if (contextKey !== 'bem' && contextKey !== 'hmj') return undefined

    let active = true
    getMyRegistrationStatus().then((result) => {
      if (active) setOrganizationName(result.data?.organisasiDetail?.nama_organisasi ?? null)
    })

    return () => {
      active = false
    }
  }, [contextKey])

  const profileName = user?.username ?? user?.nama ?? activeContext.organizationName
  const profileSubtitle = organizationName ?? activeContext.role
  const navigation = (navigationByRole[activeContext.role] ?? []).filter((item) => canUsePermission(item.permission))
  const groups = [{
    label: 'MENU UTAMA',
    items: navigation.map((item) => ({
      ...item,
      end: false,
      isActive: (isActive) => {
        const itemQuery = new URL(item.to, window.location.origin).search
        const currentQuery = new URLSearchParams(search).toString()
        return isActive && (itemQuery ? itemQuery === `?${currentQuery}` : true)
      },
    })),
  }]
  const defaultPath = contextKey === 'administrator' ? 'administrator' : contextKey === 'hmj' ? 'hmj' : contextKey === 'anggota' ? 'profil' : 'bem'
  const bemDefaultPart = canUsePermission('verify hmj/ukm') ? 'hmj' : canUsePermission('verify members') ? 'anggota' : null
  const defaultLink = contextKey === 'anggota'
    ? '/profil'
    : contextKey === 'bem' && bemDefaultPart
      ? `/verifikasi/bem?bagian=${bemDefaultPart}`
      : `/verifikasi/${defaultPath}`

  if (pathname === '/verifikasi' || pathname === '/verifikasi/') {
    return <Navigate to={defaultPath} replace />
  }

  return <div className="verification-shell">
    <header className="verification-header">
       <OrmaLogo to={defaultLink} variant="verification" className="verification-brand" />
      <div className="verification-account">
        <span className="verification-account__avatar"><Icon name="profile" size={20} /></span>
        <span><strong>{profileName}</strong><small>{profileSubtitle}</small></span>
      </div>
    </header>
    <Sidebar groups={groups} logoTo={defaultLink} ariaLabel="Navigasi verifikasi" showBrand variant="verification" />
    <main className="verification-main">
      <div className="verification-content">
        <Outlet />
      </div>
      <Footer variant="content" />
    </main>
  </div>
}

export { Icon }
export default VerificationShell
