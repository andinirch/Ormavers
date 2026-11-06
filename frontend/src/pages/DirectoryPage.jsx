import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { getCachedDirectoryCounts, getDirectoryOrganizationCounts, getDirectoryUniversities } from '../services/orgApi.js'
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
  const [lastPage, setLastPage] = useState(1)
  const requestId = useRef(0)
  const retryRef = useRef(null)

  const loadData = useCallback(async (search, targetPage = 1) => {
    const currentId = ++requestId.current
    setError('')
    if (!search.trim() && targetPage === 1) setLoading(true)
    else setSearching(true)

    try {
      const { items, meta } = await getDirectoryUniversities(search, targetPage)
      if (currentId !== requestId.current) return

      // Tampilkan grid segera; hitungan dari cache bila ada, sisanya diisi di background.
      const cachedCounts = getCachedDirectoryCounts(items)
      setUniversities(items.map((item) => ({
        ...item,
        organizationCount: cachedCounts.get(item.value) ?? null,
      })))
      setPage(meta?.current_page ?? targetPage)
      setLastPage(Math.max(1, meta?.last_page ?? 1))
      setLoading(false)
      setSearching(false)

      getDirectoryOrganizationCounts(items)
        .then((counts) => {
          if (currentId !== requestId.current) return
          setUniversities((current) => current.map((item) => ({
            ...item,
            organizationCount: counts.get(item.value) ?? item.organizationCount ?? 0,
          })))
        })
        .catch(() => {
          if (currentId !== requestId.current) return
          setUniversities((current) => current.map((item) => ({
            ...item,
            organizationCount: item.organizationCount ?? 0,
          })))
        })
    } catch (requestError) {
      if (currentId !== requestId.current) return
      setSearching(false)
      setLoading(false)
      const errorMessage = requestError.response?.data?.message ?? 'Daftar universitas gagal dimuat.'
      setError(errorMessage)

      // Show better error alerts
      if (requestError.response?.status === 429) {
        // Rate limit error
        showRateLimitError(() => retryRef.current?.(search, targetPage))
      } else if (requestError.response?.status >= 500) {
        // Server error
        showError('Server sedang bermasalah. Silakan coba lagi nanti.', () => retryRef.current?.(search, targetPage))
      } else if (!requestError.response) {
        // Network error
        showError('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.', () => retryRef.current?.(search, targetPage))
      }
    }
  }, [])
  useEffect(() => {
    retryRef.current = loadData
  }, [loadData])

  useEffect(() => {
    queueMicrotask(() => loadData('', 1))
  }, [loadData])

  const keywordMounted = useRef(false)
  useEffect(() => {
    // Lewati pemicu pertama (sudah ditangani loadData awal)
    if (!keywordMounted.current) {
      keywordMounted.current = true
      return undefined
    }
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
                  {university.organizationCount == null ? '… organisasi' : `${university.organizationCount} organisasi`}
                </span>
                </Link>
              ))}
            </section>
          )}

          {!error && universities.length > 0 && (
            <DirectoryPagination
              page={page}
              totalPages={lastPage}
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
