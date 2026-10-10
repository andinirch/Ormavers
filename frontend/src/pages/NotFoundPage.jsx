import { Link } from 'react-router-dom'

function NotFoundPage() {
  return (
    <>
      <h1>404</h1>
      <p className="auth-lead">Halaman yang Anda cari tidak ditemukan.</p>
      <div className="auth-actions">
        <Link to="/" className="primary">
          Kembali ke beranda
        </Link>
      </div>
    </>
  )
}

export default NotFoundPage
