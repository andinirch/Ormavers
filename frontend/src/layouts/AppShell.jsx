import { Link, Outlet } from 'react-router-dom'
import Footer from '../components/Footer.jsx'
import OrmaLogo from '../components/OrmaLogo.jsx'
import '../styles/status.css'

function AppShell() {
  return (
    <div className="app-shell">
      <header className="app-header">
        <div className="app-header__inner">
          <OrmaLogo to="/" variant="topbar" className="app-brand" />
           <div className="app-header__actions">
             <Link to="/login" className="app-btn-outline">Masuk</Link>
             <span className="app-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" role="img" aria-label="Profil pengguna">
                <circle cx="12" cy="8" r="3.5" />
                <path d="M5.5 20c.7-3.5 3.1-5.5 6.5-5.5s5.8 2 6.5 5.5" />
              </svg>
            </span>
          </div>
        </div>
      </header>
      <main className="app-main">
        <Outlet />
      </main>
      <Footer variant="content" />
    </div>
  )
}

export default AppShell
