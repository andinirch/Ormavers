import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getDirectoryOrganizationCounts, getDirectoryUniversities } from '../services/orgApi.js'
import { DirectorySkeleton } from '../components/skeletons'
import { showError, showRateLimitError } from '../utils/alerts.js'
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

  const loadData = async () => {
    setLoading(true)
    setError('')

    try {
      const items = await getDirectoryUniversities()
      const counts = await getDirectoryOrganizationCounts(items)
      setUniversities(items.map((item) => ({
        ...item,
        organizationCount: counts.get(item.value) ?? 0,
      })))
      setLoading(false)
    } catch (requestError) {
      setLoading(false)
      const errorMessage = requestError.response?.data?.message ?? 'Daftar universitas gagal dimuat.'
      setError(errorMessage)

      // Show better error alerts
      if (requestError.response?.status === 429) {
        // Rate limit error
        showRateLimitError(() => loadData())
      } else if (requestError.response?.status >= 500) {
        // Server error
        showError('Server sedang bermasalah. Silakan coba lagi nanti.', () => loadData())
      } else if (!requestError.response) {
        // Network error
        showError('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.', () => loadData())
      }
    }
  }

  useEffect(() => {
    loadData()
  }, [])

  return (
    <div className="directory-shell">
      {loading && <DirectorySkeleton />}
      
      {!loading && (
        <main className="directory-main">
          <div className="directory-heading">
            <h1>Direktori Organisasi</h1>
            <p>Pilih universitas untuk melihat organisasi mahasiswa PTMA Jawa Timur.</p>
          </div>

          {error && (
            <div className="directory-state directory-state--error" role="alert">
              {error}
              <button 
                onClick={loadData} 
                style={{ 
                  marginTop: '16px', 
                  padding: '8px 16px', 
                  backgroundColor: '#2563eb', 
                  color: '#fff', 
                  border: 'none', 
                  borderRadius: '8px', 
                  cursor: 'pointer' 
                }}
              >
                Coba Lagi
              </button>
            </div>
          )}
          
          {!error && universities.length === 0 && (
            <div className="directory-state">Belum ada data universitas yang tersedia.</div>
          )}
          
          {!error && universities.length > 0 && (
            <section className="directory-university-grid" aria-label="Daftar universitas">
              {universities.map((university) => (
                <Link className="directory-university-card" key={university.value} to={`universitas/${university.value}`}>
                  <div className="directory-university__logo"><UniversityLogo university={university} /></div>
                  <div className="directory-university__copy">
                    <h2>{university.label}</h2>
                  </div>
                <span className="directory-university__count">
                  {university.organizationCount} organisasi
                </span>
                </Link>
              ))}
            </section>
          )}
        </main>
      )}
    </div>
  )
}

export default DirectoryPage
