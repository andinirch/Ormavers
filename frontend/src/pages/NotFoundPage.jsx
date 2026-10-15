import { Link } from 'react-router-dom'

function NotFoundPage() {
  return (
    <>
      <h1>404</h1>
      <p className="auth-alt" style={{ marginTop: 0 }}>
        Halaman yang Anda cari tidak ditemukan.
      </p>
      <p className="auth-alt">
        <Link to="/">Kembali ke beranda</Link>
      </p>
    </>
  )
}

export default NotFoundPage
