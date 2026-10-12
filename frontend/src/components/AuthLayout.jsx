import { Outlet, useLocation } from 'react-router-dom'
import heroImg from '../assets/hero.png'
import '../styles/auth.css'

function AuthLayout() {
  const { pathname } = useLocation()

  if (
    pathname === '/login' ||
    pathname === '/registrasi/bem' ||
    pathname === '/registrasi/hmj-ukm' ||
    pathname === '/registrasi/anggota'
  ) {
    return <Outlet />
  }

  return (
    <div className="auth-shell">
      <div className="auth-hero">
        <img src={heroImg} alt="" className="auth-hero__img" />
      </div>
      <div className="auth-panel">
        <div className="auth-panel__inner">
          <p className="auth-brand">ORMAVERS</p>
          <div className="auth-card">
            <Outlet />
          </div>
        </div>
      </div>
    </div>
  )
}

export default AuthLayout
