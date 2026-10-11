import { Link, Outlet } from 'react-router-dom'
import '../styles/status.css'

const navItems = [
  { label: 'Pendaftaran' },
  { label: 'Katalog Ormawa' },
  { label: 'Pusat Bantuan' },
]

function AppShell() {
  return (
    <div className="app-shell">
      <header className="app-header">
        <div className="app-header__inner">
          <Link to="/" className="app-brand">
            <span className="app-brand__icon" aria-hidden="true">O</span>
            <span className="app-brand__text">Ormavers</span>
          </Link>
          <nav className="app-nav" aria-label="Navigasi utama">
            {navItems.map((item) => (
              <span key={item.label} className="app-nav__link app-nav__link--disabled" aria-disabled="true">
                {item.label}
              </span>
            ))}
          </nav>
          <div className="app-header__actions">
            <Link to="/login" className="app-btn-outline">Masuk</Link>
            <span className="app-avatar" aria-hidden="true" />
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
