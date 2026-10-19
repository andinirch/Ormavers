import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import '../styles/forbidden.css'

export default function ForbiddenPage() {
  const { user } = useAuth()
  const dashboardPath = user?.peran === 'super_admin' ? '/superadmin' : '/admin'
  return (
    <main className="forbidden-page">
      <section className="forbidden-card" aria-labelledby="forbidden-title">
        <span className="forbidden-code">403</span>
        <p className="forbidden-eyebrow">AKSES TERBATAS</p>
        <h1 id="forbidden-title">Halaman ini tidak dapat diakses</h1>
        <p>Peran atau izin akun Anda tidak mencakup halaman yang diminta.</p>
        <Link className="forbidden-action" to={dashboardPath}>Kembali ke dashboard</Link>
      </section>
    </main>
  )
}
