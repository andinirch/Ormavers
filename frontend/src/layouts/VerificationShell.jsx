import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { verificationContexts } from '../services/verification/verificationApi.js'
import '../styles/verification.css'

const navigationByRole = {
  Administrator: [
    { to: '/verifikasi/administrator', label: 'Daftar Registrasi BEM', icon: 'bem' },
  ],
  'Akun Organisasi BEM': [
    { to: '/verifikasi/bem?bagian=hmj', label: 'Daftar Registrasi HMJ/UKM', icon: 'hmj' },
    { to: '/verifikasi/bem?bagian=anggota', label: 'Daftar Permintaan Anggota', icon: 'member' },
  ],
  'Akun Organisasi HMJ/UKM': [
    { to: '/verifikasi/hmj-ukm', label: 'Daftar Permintaan Anggota', icon: 'member' },
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
  const organizationKey = new URLSearchParams(search).get('organisasi')
  const activeContext = pathname === '/verifikasi/administrator'
    ? verificationContexts.administrator
    : pathname === '/verifikasi/bem'
      ? verificationContexts.bemUmg
      : pathname === '/verifikasi/hmj-ukm'
        ? verificationContexts[organizationKey === 'ukm-robotika' ? 'ukmRobotika' : 'hmif']
        : verificationContexts[organizationKey === 'hmif' ? 'hmif' : organizationKey === 'ukm-robotika' ? 'ukmRobotika' : 'bemUmg']
  const navigation = navigationByRole[activeContext.role] ?? []

  return <div className="verification-shell">
    <header className="verification-header">
      <NavLink to="/verifikasi/bem" className="verification-brand">
        <img src="/images/OrmaversLogo.png" alt="" className="verification-brand__logo" />
        <span>Ormavers</span>
      </NavLink>
      <div className="verification-account">
        <span className="verification-account__avatar"><Icon name="profile" size={20} /></span>
        <span><strong>{activeContext.organizationName}</strong><small>{activeContext.role}</small></span>
      </div>
    </header>
    <aside className="verification-sidebar">
      <nav aria-label="Navigasi verifikasi">
        {navigation.map((item) => <NavLink key={item.to} to={item.to} className={({ isActive }) => {
          const itemQuery = new URL(item.to, window.location.origin).search
          const currentQuery = new URLSearchParams(search).toString()
          const queryMatches = itemQuery === `?${currentQuery}`
          return `verification-nav__item${isActive && (itemQuery ? queryMatches : true) ? ' is-active' : ''}`
        }}>
          <Icon name={item.icon} /> <span>{item.label}</span>
        </NavLink>)}
        <span className="verification-nav__item is-disabled" aria-disabled="true"><Icon name="profile" /><span>Profil Saya</span></span>
      </nav>
      <span className="verification-logout is-disabled" aria-disabled="true"><Icon name="logout" /><span>Keluar</span></span>
    </aside>
    <main className="verification-main"><Outlet /></main>
  </div>
}

export { Icon }
export default VerificationShell
