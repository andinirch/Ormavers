/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import ImageWithFallback from '../../components/ImageWithFallback.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import { assetUrl } from '../../api/api.js'
import { getEventCategories, getExploredEvents } from '../../api/eventExploration.js'
import { sortCategories } from '../../utils/categoryConstants.js'
import '../../styles/event-exploration.css'

const PAGE_SIZE = 12
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

function unwrap(result) {
  if (Array.isArray(result?.data)) return result.data
  if (Array.isArray(result?.data?.data)) return result.data.data
  if (Array.isArray(result?.data?.events)) return result.data.events
  return []
}

function unwrapMeta(result) {
  if (result?.meta) return result.meta
  if (result?.data?.meta) return result.data.meta
  return null
}
function formatDate(value) { if (!value) return '-'; const [date] = String(value).split(/[ T]/); const [year, month, day] = date.split('-').map(Number); return year && month && day ? `${day} ${MONTHS[month - 1]} ${year}` : value }
function posterUrl(poster) {
  const raw = poster?.url || poster?.path_file || poster?.image || poster?.thumbnail || poster?.cover_image || poster?.banner
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(String(raw))) return assetUrl(String(raw))
  return assetUrl(String(raw).startsWith('/storage/') ? String(raw) : `/storage/${String(raw).replace(/^\/+/, '')}`)
}
function formatDateInput(value) { return value ? formatDate(value) : 'Pilih tanggal' }
function eventStatus(event) { const date = new Date(`${event.tanggal_event}T00:00:00`); if (!Number.isNaN(date.getTime()) && date < new Date(new Date().toDateString())) return ['Selesai', 'is-done']; const value = event.status_pendaftaran || 'Belum Dibuka'; return [value, value === 'Dibuka' ? 'is-open' : value === 'Belum Dibuka' ? 'is-soon' : 'is-closed'] }

function ErrorState({ retry }) { return <div className="exploration-state"><div className="exploration-state__illustration is-error"><AdminIcon name="alert-circle" size={42} /></div><h2>Gagal memuat event.</h2><p>Terjadi kesalahan saat mengambil data. Periksa koneksi Anda.</p><button className="exploration-outline-button" onClick={retry}>Coba lagi</button></div> }
function Skeletons() { return <div className="exploration-grid">{Array.from({ length: PAGE_SIZE }, (_, index) => <div className="exploration-card exploration-card--skeleton" key={index}><div className="exploration-skeleton exploration-skeleton--poster" /><div className="exploration-skeleton exploration-skeleton--line" /><div className="exploration-skeleton exploration-skeleton--small" /><div className="exploration-skeleton exploration-skeleton--button" /></div>)}</div> }
function PosterFallback() {
  return <span className="exploration-card__fallback"><AdminIcon name="image" size={30} /></span>
}
const categoryRules = [
  [['kepemimpinan', 'recruitment', 'kaderisasi', 'musyawarah', 'pelantikan', 'serah terima', 'rapat', 'forum'], 'organisasi'],
  [['seminar', 'kuliah', 'diskusi', 'bedah buku'], 'seminar'],
  [['workshop'], 'workshop'],
  [['pelatihan'], 'pelatihan'],
  [['lomba', 'kompetisi'], 'lomba'],
  [['kajian', 'islam', 'ramadan'], 'kajian'],
  [['sosial', 'kemanusiaan', 'bencana', 'pengabdian'], 'sosial'],
  [['seni', 'budaya'], 'seni'],
  [['olahraga'], 'olahraga'],
  [['wirausaha'], 'wirausaha'],
]
function categoryStyleFor(category) {
  const name = String(category ?? '').trim().toLowerCase()
  const match = categoryRules.find(([keywords]) => keywords.some((word) => name.includes(word)))
  const key = match ? match[1] : 'default'
  return { border: `is-category-${key}`, pill: `is-category-${key}` }
}
function EventCard({ event }) {
  const [status, statusClass] = eventStatus(event)
  const image = posterUrl(event.poster_utama || event.poster || event.image || event.thumbnail)
  const category = event.nama_kategori || event.kategori || event.tag || 'Lainnya'
  const categoryStyle = categoryStyleFor(category)
  return <article className={`exploration-card ${categoryStyle.border}${statusClass === 'is-done' ? ' exploration-card--done' : ''}`}>
    <div className="exploration-card__poster-wrap">
      <Link className="exploration-card__poster" style={image ? { '--poster-url': `url("${image}")` } : undefined} to={`/jelajah-event/${event.id}`}>
        {image ? <ImageWithFallback src={image} alt={event.judul} fallback={<PosterFallback />} /> : <PosterFallback />}
        <span className={`exploration-card__badge exploration-card__badge--status ${statusClass}`}>{status}</span>
      </Link>
    </div>
    <div className="exploration-card__body">
      <h2>{event.judul}</h2>
      <div className="exploration-card__meta-line"><span className="exploration-card__organization-pill">{event.organisasi?.nama_organisasi || 'Organisasi penyelenggara'}</span><span className={`exploration-card__category-pill ${categoryStyle.pill}`}>{category}</span><span className="exploration-card__date-pill"><AdminIcon name="calendar" size={12} /> {formatDate(event.tanggal_event)}</span></div>
      <Link className="exploration-primary-button exploration-primary-button--full" to={`/jelajah-event/${event.id}`}>Lihat detail</Link>
    </div>
  </article>
}
function CategorySelect({ value, categories, onChange }) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef(null)

  const selectedCategory = categories.find((c) => String(c.id) === String(value))
  const displayLabel = selectedCategory ? selectedCategory.nama_kategori : 'Semua Kategori'

  useEffect(() => {
    function handleClickOutside(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleSelect = (id) => {
    onChange({ target: { value: id } })
    setIsOpen(false)
  }

  return (
    <div className="exploration-custom-dropdown" ref={containerRef}>
      <button
        type="button"
        className={`exploration-custom-dropdown__trigger${!value ? ' is-placeholder' : ''}${isOpen ? ' is-open' : ''}`}
        onClick={() => setIsOpen((prev) => !prev)}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
      >
        <span className="exploration-custom-dropdown__label">{displayLabel}</span>
        <AdminIcon name="chevron-down" size={16} />
      </button>

      {isOpen && (
        <ul className="exploration-custom-dropdown__menu" role="listbox">
          <li
            role="option"
            aria-selected={!value}
            className={`exploration-custom-dropdown__item${!value ? ' is-selected' : ''}`}
            onClick={() => handleSelect('')}
          >
            Semua Kategori
          </li>
          {categories.map((item) => {
            const isSelected = String(item.id) === String(value)
            return (
              <li
                key={item.id}
                role="option"
                aria-selected={isSelected}
                className={`exploration-custom-dropdown__item${isSelected ? ' is-selected' : ''}`}
                onClick={() => handleSelect(item.id)}
              >
                {item.nama_kategori}
              </li>
            )
          })}
        </ul>
      )}
    </div>
  )
}
function DatePicker({ value, onChange }) {
  const inputRef = useRef(null)

  const handleOpen = () => {
    const el = inputRef.current
    if (!el) return
    if (typeof el.showPicker === 'function') {
      try {
        el.showPicker()
        return
      } catch {
        /* fallback ke focus */
      }
    }
    el.focus()
  }

  const handleClear = (e) => {
    e.stopPropagation()
    onChange({ target: { value: '' } })
  }

  return (
    <div
      className={`exploration-date-picker${value ? '' : ' is-placeholder'}`}
      onClick={handleOpen}
      role="button"
      tabIndex={0}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault()
          handleOpen()
        }
      }}
    >
      <span>{formatDateInput(value)}</span>
      {value ? (
        <button
          type="button"
          className="exploration-date-picker__clear"
          onClick={handleClear}
          aria-label="Hapus filter tanggal"
        >
          <AdminIcon name="close" size={14} />
        </button>
      ) : (
        <AdminIcon name="calendar" size={16} />
      )}
      <input
        ref={inputRef}
        type="date"
        value={value}
        onChange={onChange}
        aria-label="Tanggal event"
        className="exploration-date-picker__native"
        tabIndex={-1}
      />
    </div>
  )
}

export default function EventExplorationPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const [keyword, setKeyword] = useState(searchParams.get('q') || '')
  const [category, setCategory] = useState(searchParams.get('id_kategori') || '')
  const [date, setDate] = useState(searchParams.get('tanggal_mulai') || '')
  const [events, setEvents] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [page, setPage] = useState(() => {
    const p = parseInt(searchParams.get('page'), 10)
    return Number.isFinite(p) && p > 0 ? p : 1
  })
  const [paginationMeta, setPaginationMeta] = useState(null)

  const load = async (targetPage = page) => {
    setLoading(true)
    setError('')
    try {
      const result = await getExploredEvents({
        page: targetPage,
        ...(keyword && { q: keyword }),
        ...(category && { id_kategori: category }),
        ...(date && { tanggal_mulai: date, tanggal_selesai: date }),
      })
      const items = unwrap(result)
      const meta = unwrapMeta(result)
      setEvents(items)
      setPaginationMeta(meta)
      setPage(meta?.current_page ?? targetPage)
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'Gagal memuat event.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    getEventCategories()
      .then((result) => setCategories(sortCategories(unwrap(result))))
      .catch(() => {})
  }, [])

  useEffect(() => {
    const urlPage = parseInt(searchParams.get('page'), 10)
    const initialPage = Number.isFinite(urlPage) && urlPage > 0 ? urlPage : 1
    load(initialPage)
  }, [])

  const active = Boolean(keyword || category || date)

  const currentPage = paginationMeta?.current_page ?? page
  const totalPages = paginationMeta?.last_page ?? Math.max(1, Math.ceil(events.length / PAGE_SIZE))

  const handlePageChange = (newPage) => {
    if (newPage === currentPage || newPage < 1 || newPage > totalPages) return
    const nextParams = {
      ...(keyword && { q: keyword }),
      ...(category && { id_kategori: category }),
      ...(date && { tanggal_mulai: date }),
    }
    if (newPage > 1) {
      nextParams.page = newPage
    }
    setSearchParams(nextParams)
    load(newPage)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const submit = (event) => {
    event.preventDefault()
    setSearchParams({
      ...(keyword && { q: keyword }),
      ...(category && { id_kategori: category }),
      ...(date && { tanggal_mulai: date }),
    })
    load(1)
  }

  const handleResetFilters = () => {
    setKeyword('')
    setCategory('')
    setDate('')
    setSearchParams({})
    setTimeout(() => {
      getExploredEvents({ page: 1 })
        .then((res) => {
          setEvents(unwrap(res))
          setPaginationMeta(unwrapMeta(res))
          setPage(1)
        })
        .catch(() => {})
    }, 0)
  }

  const pageNumbers = useMemo(() => {
    if (totalPages <= 5) {
      return Array.from({ length: totalPages }, (_, i) => i + 1)
    }
    const start = Math.max(1, Math.min(currentPage - 2, totalPages - 4))
    return Array.from({ length: 5 }, (_, i) => start + i)
  }, [totalPages, currentPage])

  return (
    <main className="event-exploration-page">
      <PageHeader
        title="Jelajah Event"
        subtitle="Temukan event dari organisasi lain sesuai jangkauan publikasinya."
        breadcrumb={['Ormavers', 'Jelajah Event']}
        icon="compass"
      />
      <form className="exploration-filters" onSubmit={submit}>
        <div className="exploration-search">
          <AdminIcon name="search" size={17} />
          <input
            value={keyword}
            onChange={(event) => setKeyword(event.target.value)}
            placeholder="Cari judul atau deskripsi event..."
            aria-label="Cari judul atau deskripsi event"
            onKeyDown={(event) => {
              if (event.key === 'Enter') {
                submit(event)
              }
            }}
          />
          {keyword && (
            <button
              type="button"
              className="exploration-search__clear"
              onClick={() => setKeyword('')}
              aria-label="Hapus kata kunci"
            >
              <AdminIcon name="close" size={14} />
            </button>
          )}
        </div>
        <CategorySelect value={category} categories={categories} onChange={(event) => setCategory(event.target.value)} />
        <DatePicker value={date} onChange={(event) => setDate(event.target.value)} />
        <button className="exploration-primary-button" type="submit">
          <AdminIcon name="search" size={16} /> Cari
        </button>
      </form>
      {loading ? (
        <Skeletons />
      ) : error ? (
        <ErrorState retry={() => load(currentPage)} />
      ) : events.length === 0 ? (
        <div className="exploration-state">
          <div className="exploration-state__illustration">
            <AdminIcon name={active ? 'search' : 'calendar'} size={42} />
          </div>
          <h2>{active ? 'Event tidak ditemukan.' : 'Belum ada event.'}</h2>
          <p>
            {active
              ? 'Tidak ada event yang cocok dengan kriteria pencarian atau filter yang Anda terapkan. Coba gunakan kata kunci lain.'
              : 'Saat ini belum ada event publik yang dipublikasikan oleh organisasi mitra. Periksa kembali nanti.'}
          </p>
          {active && (
            <button type="button" className="exploration-outline-button" onClick={handleResetFilters}>
              Reset Semua Filter
            </button>
          )}
        </div>
      ) : (
        <>
          <div className="exploration-grid">
            {events.map((event) => (
              <EventCard event={event} key={event.id} />
            ))}
          </div>
          {totalPages > 1 && (
            <div className="exploration-pagination">
              <span>
                Halaman {currentPage} dari {totalPages}
                {paginationMeta?.total != null ? ` (${paginationMeta.total} total event)` : ''}
              </span>
              <div>
                <button
                  className="exploration-pagination__button"
                  disabled={currentPage <= 1}
                  onClick={() => handlePageChange(currentPage - 1)}
                >
                  Sebelumnya
                </button>
                {pageNumbers.map((number) => (
                  <button
                    key={number}
                    className={`exploration-pagination__button${number === currentPage ? ' is-current' : ''}`}
                    onClick={() => handlePageChange(number)}
                  >
                    {number}
                  </button>
                ))}
                <button
                  className="exploration-pagination__button"
                  disabled={currentPage >= totalPages}
                  onClick={() => handlePageChange(currentPage + 1)}
                >
                  Berikutnya
                </button>
              </div>
            </div>
          )}
        </>
      )}
    </main>
  )
}
