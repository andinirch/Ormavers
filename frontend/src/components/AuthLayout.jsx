import { NavLink, Outlet } from 'react-router-dom'
import '../styles/auth.css'

const navItems = [
  { to: '/', label: 'Beranda', end: true },
  { to: '/login', label: 'Login' },
  { to: '/registrasi/bem', label: 'Registrasi BEM' },
  { to: '/registrasi/hmj-ukm', label: 'Registrasi HMJ/UKM' },
  { to: '/registrasi/anggota', label: 'Registrasi Anggota' },
]

function AuthLayout() {
  return (
    <div className="auth-shell">
      <header className="auth-header">
        <div className="auth-header__inner">
          <NavLink to="/" className="auth-brand" end>
            ORMAVERS
          </NavLink>
          <nav className="auth-nav" aria-label="Navigasi utama">
            {navItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.end}
                className={({ isActive }) =>
                  isActive ? 'auth-nav__link active' : 'auth-nav__link'
                }
              >
                {item.label}
              </NavLink>
            ))}
          </nav>
        </div>
      </header>
      <main className="auth-main">
        <Outlet />
      </main>
    </div>
  )
}

export default AuthLayout
