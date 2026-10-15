import { Link, Outlet } from 'react-router-dom'
import '../styles/status.css'

function AppShell() {
  return (
    <div className="app-shell">
      <header className="app-header">
        <div className="app-header__inner">
          <Link to="/" className="app-brand">
            <img src="/images/OrmaversLogo.png" alt="" className="app-brand__logo" />
            <span className="app-brand__text">Ormavers</span>
          </Link>
          <div className="app-header__actions">
            <button type="button" className="app-btn-outline">Masuk</button>
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
      <footer className="app-footer">
        <div className="app-footer__inner">
          <p className="app-footer__copy">&copy; 2025 Ormavers &mdash; Ekosistem Kolaborasi Ormawa Muhammadiyah Jawa Timur.</p>
          <div className="app-footer__links">
            <span className="app-footer__link" aria-disabled="true">Panduan Layanan</span>
            <span className="app-footer__link" aria-disabled="true">Kontak Bantuan</span>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default AppShell
