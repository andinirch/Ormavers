import { Link } from 'react-router-dom'

function HomePage() {
  return (
    <>
      <h1>ORMAVERS</h1>
      <p className="auth-lead">
        Sistem informasi organisasi mahasiswa BEM, HMJ, UKM, dan anggota di
        lingkungan PTMA Jawa Timur.
      </p>
      <p className="auth-note">
        Halaman utama (landing page) sedang disiapkan pada branch terpisah.
      </p>
      <div className="auth-actions">
        <Link to="/login" className="primary">
          Masuk
        </Link>
        <Link to="/registrasi/bem">Registrasi BEM</Link>
        <Link to="/registrasi/hmj-ukm">Registrasi HMJ/UKM</Link>
        <Link to="/registrasi/anggota">Registrasi Anggota</Link>
      </div>
    </>
  )
}

export default HomePage
