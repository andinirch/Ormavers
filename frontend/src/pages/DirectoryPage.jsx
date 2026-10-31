import { useEffect, useRef, useState } from 'react'
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

const PER_PAGE = 6
const MAX_PAGES = 3

function DirectoryPagination({ page, totalPages, onChange, disabled }) {
  if (totalPages <= 1) return null
  return (
    <nav className="directory-pagination" aria-label="Pagination direktori">
      <span className="directory-pagination__info">Halaman <strong>{page}</strong> dari {totalPages}</span>
      <div className="directory-pagination__controls">
        <button type="button" onClick={() => onChange(page - 1)} disabled={disabled || page <= 1}>
          &lt; Sebelumnya
        </button>
        {Array.from({ length: totalPages }, (_, index) => index + 1).map((item) => (
          <button
            type="button"
            key={item}
            className={item === page ? 'is-active' : ''}
            aria-current={item === page ? 'page' : undefined}
            onClick={() => onChange(item)}
            disabled={disabled}
          >
            {item}
          </button>
        ))}
        <button type="button" onClick={() => onChange(page + 1)} disabled={disabled || page >= totalPages}>
          Berikutnya &gt;
        </button>
      </div>
    </nav>
  )
}

function DirectoryPage() {
  const [universities, setUniversities] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [keyword, setKeyword] = useState('')
  const [searching, setSearching] = useState(false)
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(null)
  const requestId = useRef(0)

  const loadData = async (search = keyword, targetPage = 1) => {
    const currentId = ++requestId.current
    setError('')
    if (!search.trim() && targetPage === 1) setLoading(true)
    else setSearching(true)

    try {
      const items = await getDirectoryUniversities(search, targetPage)
      if (currentId !== requestId.current) return

      if (items.length === 0 && targetPage > 1) {
        setLastPage(targetPage - 1)
        setSearching(false)
        loadData(search, targetPage - 1)
        return
      }

      const counts = await getDirectoryOrganizationCounts(items)
      if (currentId !== requestId.current) return
      setUniversities(items.map((item) => ({
        ...item,
        organizationCount: counts.get(item.value) ?? 0,
      })))
      setPage(targetPage)
      if (items.length < PER_PAGE) setLastPage(targetPage)
      else setLastPage((current) => (current && current > targetPage ? current : null))
      setLoading(false)
      setSearching(false)
    } catch (requestError) {
      if (currentId !== requestId.current) return
      setSearching(false)
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
    loadData('', 1)
  }, [])

  useEffect(() => {
    if (loading) return undefined
    const timer = setTimeout(() => {
      loadData(keyword, 1)
    }, 300)
    return () => clearTimeout(timer)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [keyword])

  return (
    <div className="directory-shell">
      {loading && <DirectorySkeleton />}
      
      {!loading && (
        <main className="directory-main">
          <div className="directory-heading">
            <h1>Direktori Organisasi</h1>
            <p>Pilih universitas untuk melihat organisasi mahasiswa PTMA Jawa Timur.</p>
          </div>

          <section className="directory-search" aria-label="Cari organisasi">
            <span className="directory-search__title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
              Cari Universitas
            </span>
            <label className="directory-search__field">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
              <input
                type="search"
                value={keyword}
                onChange={(event) => setKeyword(event.target.value)}
                placeholder="Cari nama universitas..."
              />
            </label>
          </section>

          {error && (
            <div className="directory-state directory-state--error" role="alert">
              {error}
              <button 
                onClick={() => loadData(keyword, page)} 
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
            <div className="directory-state">
              {keyword.trim() ? 'Universitas tidak ditemukan.' : 'Belum ada data universitas yang tersedia.'}
            </div>
          )}
          
          {!error && universities.length > 0 && (
            <section className={`directory-university-grid${searching ? ' is-searching' : ''}`} aria-label="Daftar universitas" aria-busy={searching}>
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

          {!error && universities.length > 0 && (
            <DirectoryPagination
              page={page}
              totalPages={Math.min(MAX_PAGES, lastPage ?? MAX_PAGES)}
              disabled={searching}
              onChange={(target) => loadData(keyword, target)}
            />
          )}
        </main>
      )}
    </div>
  )
}

export default DirectoryPage
