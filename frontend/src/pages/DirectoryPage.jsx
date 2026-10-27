import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getUniversitas, getUniversityOrganizationCounts } from '../services/orgApi.js'
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
      .then(async (items) => {
        const counts = await getUniversityOrganizationCounts(items)
        const enrichedItems = items.map((item) => ({
          ...item,
          organizationCount: counts.get(item.value) ?? 0,
        }))
        if (active) setUniversities(enrichedItems)
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
          <p>Pilih universitas untuk melihat organisasi mahasiswa PTMA Jawa Timur.</p>
        </div>

        {loading && <div className="directory-state" role="status">Memuat daftar universitas...</div>}
        {!loading && error && <div className="directory-state directory-state--error" role="alert">{error}</div>}
        {!loading && !error && universities.length === 0 && (
          <div className="directory-state">Belum ada data universitas yang tersedia.</div>
        )}
        {!loading && !error && universities.length > 0 && (
          <section className="directory-university-grid" aria-label="Daftar universitas">
            {universities.map((university) => (
              <Link className="directory-university-card" key={university.value} to={`universitas/${university.value}`}>
                <div className="directory-university__logo"><UniversityLogo university={university} /></div>
                <div className="directory-university__copy">
                  <h2>{university.label}</h2>
                </div>
                <span className="directory-university__count">
                  <svg className="directory-university__count-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3" /><path d="M3 19a6 6 0 0 1 12 0" /><path d="M16 6a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5" /></svg>
                  {university.organizationCount} organisasi
                </span>
              </Link>
            ))}
          </section>
        )}
      </main>
    </div>
  )
}

export default DirectoryPage
