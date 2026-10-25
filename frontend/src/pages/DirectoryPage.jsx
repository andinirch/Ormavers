import { useEffect, useState } from 'react'
import { getUniversitas } from '../services/orgApi.js'
import '../styles/directory.css'

function UniversityLogo({ university }) {
  const [failed, setFailed] = useState(false)

  if (!university.logo || failed) {
    return <span className="directory-university__fallback" aria-hidden="true">PTMA</span>
  }

  return <img src={university.logo} alt="" onError={() => setFailed(true)} />
}

function DirectoryPage() {
  const [universities, setUniversities] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    getUniversitas()
      .then((items) => {
        if (active) setUniversities(items)
      })
      .catch((requestError) => {
        if (active) setError(requestError.response?.data?.message ?? 'Daftar universitas gagal dimuat.')
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => { active = false }
  }, [])

  return (
    <div className="directory-shell">
      <main className="directory-main">
        <div className="directory-heading">
          <h1>Direktori Universitas</h1>
          <p>Temukan organisasi mahasiswa PTMA Jawa Timur dan kenali jaringan kolaborasinya.</p>
        </div>

        {loading && <div className="directory-state" role="status">Memuat daftar universitas...</div>}
        {!loading && error && <div className="directory-state directory-state--error" role="alert">{error}</div>}
        {!loading && !error && universities.length === 0 && (
          <div className="directory-state">Belum ada data universitas yang tersedia.</div>
        )}
        {!loading && !error && universities.length > 0 && (
          <section className="directory-university-grid" aria-label="Daftar universitas">
            {universities.map((university) => (
              <article className="directory-university-card" key={university.value}>
                <div className="directory-university__logo"><UniversityLogo university={university} /></div>
                <div className="directory-university__copy">
                  <h2>{university.label}</h2>
                  <span>PTMA Jawa Timur</span>
                </div>
                <button type="button" className="directory-university__action" disabled>
                  Lihat organisasi
                </button>
              </article>
            ))}
          </section>
        )}
      </main>
    </div>
  )
}

export default DirectoryPage
