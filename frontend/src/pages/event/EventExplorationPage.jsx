/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import ImageWithFallback from '../../components/ImageWithFallback.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import { assetUrl } from '../../api/api.js'
import { getEventCategories, getExploredEvents } from '../../api/eventExploration.js'
import '../../styles/event-exploration.css'

const PAGE_SIZE = 6
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

function unwrap(result) { return Array.isArray(result?.data) ? result.data : Array.isArray(result?.data?.events) ? result.data.events : [] }
function formatDate(value) { if (!value) return '-'; const [date] = String(value).split(/[ T]/); const [year, month, day] = date.split('-').map(Number); return year && month && day ? `${day} ${MONTHS[month - 1]} ${year}` : value }
function posterUrl(poster) {
  const raw = poster?.url || poster?.path_file || poster?.image || poster?.thumbnail || poster?.cover_image || poster?.banner
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(String(raw))) return assetUrl(String(raw))
  return assetUrl(String(raw).startsWith('/storage/') ? String(raw) : `/storage/${String(raw).replace(/^\/+/, '')}`)
}
function formatDateInput(value) { return value ? formatDate(value) : 'Pilih tanggal' }
function eventStatus(event) { const date = new Date(`${event.tanggal_event}T00:00:00`); if (!Number.isNaN(date.getTime()) && date < new Date(new Date().toDateString())) return ['Selesai', 'is-done']; const value = event.status_pendaftaran || 'Belum Dibuka'; return [value, value === 'Dibuka' ? 'is-open' : 'is-closed'] }

function EmptyState({ filtered }) {
  return <div className="exploration-state"><div className="exploration-state__illustration"><AdminIcon name={filtered ? 'search' : 'calendar'} size={42} /></div><h2>{filtered ? 'Event tidak ditemukan.' : 'Belum ada event.'}</h2><p>{filtered ? 'Tidak ada event yang cocok dengan kriteria pencarian atau filter yang Anda terapkan. Coba gunakan kata kunci lain.' : 'Saat ini belum ada event publik yang dipublikasikan oleh organisasi mitra. Periksa kembali nanti.'}</p></div>
}
function ErrorState({ retry }) { return <div className="exploration-state"><div className="exploration-state__illustration is-error"><AdminIcon name="alert-circle" size={42} /></div><h2>Gagal memuat event.</h2><p>Terjadi kesalahan saat mengambil data. Periksa koneksi Anda.</p><button className="exploration-outline-button" onClick={retry}>Coba lagi</button></div> }
function Skeletons() { return <div className="exploration-grid">{Array.from({ length: PAGE_SIZE }, (_, index) => <div className="exploration-card exploration-card--skeleton" key={index}><div className="exploration-skeleton exploration-skeleton--poster" /><div className="exploration-skeleton exploration-skeleton--line" /><div className="exploration-skeleton exploration-skeleton--small" /><div className="exploration-skeleton exploration-skeleton--button" /></div>)}</div> }
function PosterFallback() {
  return <span className="exploration-card__fallback"><AdminIcon name="image" size={30} /></span>
}
const categoryStyles = {
  seminar: { border: 'is-category-seminar', pill: 'is-category-seminar' },
  pelatihan: { border: 'is-category-pelatihan', pill: 'is-category-pelatihan' },
  kajian: { border: 'is-category-kajian', pill: 'is-category-kajian' },
  lomba: { border: 'is-category-lomba', pill: 'is-category-lomba' },
  kompetisi: { border: 'is-category-lomba', pill: 'is-category-lomba' },
  workshop: { border: 'is-category-workshop', pill: 'is-category-workshop' },
}
function EventCard({ event }) {
  const [status, statusClass] = eventStatus(event)
  const image = posterUrl(event.poster_utama || event.poster || event.image || event.thumbnail)
  const category = event.nama_kategori || event.kategori || event.tag || 'Lainnya'
  const categoryStyle = categoryStyles[String(category).trim().toLowerCase()] || { border: 'is-category-default', pill: 'is-category-default' }
  return <article className={`exploration-card ${categoryStyle.border}`}>
    <div className="exploration-card__poster-wrap">
      <Link className="exploration-card__poster" to={`/jelajah-event/${event.id}`}>
        {image ? <ImageWithFallback src={image} alt={event.judul} fallback={<PosterFallback />} /> : <PosterFallback />}
        <span className={`exploration-card__badge exploration-card__badge--status ${statusClass}`}>{status}</span>
      </Link>
    </div>
    <div className="exploration-card__body">
      <h2>{event.judul}</h2>
      <div className="exploration-card__meta-line"><span className="exploration-card__organization-pill">{event.organisasi?.nama_organisasi || 'Organisasi penyelenggara'}</span><span className={`exploration-card__category-pill ${categoryStyle.pill}`}>{category}</span><span className="exploration-card__date-pill"><AdminIcon name="calendar" size={12} /> {formatDate(event.tanggal_event)}</span></div>
      <Link className="exploration-primary-button exploration-primary-button--full" to={`/jelajah-event/${event.id}`}>Lihat detail <span aria-hidden="true">→</span></Link>
    </div>
  </article>
}
function CategorySelect({ value, categories, onChange }) {
  return <label className={`exploration-custom-select${value ? '' : ' is-placeholder'}`}><select value={value} onChange={onChange} aria-label="Kategori"><option value="">Semua Kategori</option>{categories.map((item) => <option key={item.id} value={item.id}>{item.nama_kategori}</option>)}</select><AdminIcon name="chevron-down" size={16} /></label>
}
function DatePicker({ value, onChange }) {
  const inputRef = useRef(null)
  return <label className={`exploration-date-picker${value ? '' : ' is-placeholder'}`}><span>{formatDateInput(value)}</span><AdminIcon name="calendar" size={16} /><input ref={inputRef} type="date" value={value} onChange={onChange} aria-label="Tanggal event" /></label>
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
  const [page, setPage] = useState(1)
  const load = async () => { setLoading(true); setError(''); try { const result = await getExploredEvents({ ...(keyword && { q: keyword }), ...(category && { id_kategori: category }), ...(date && { tanggal_mulai: date, tanggal_selesai: date }) }); setEvents(unwrap(result)); setPage(1) } catch (requestError) { setError(requestError.response?.data?.message || 'Gagal memuat event.') } finally { setLoading(false) } }
  useEffect(() => { getEventCategories().then((result) => setCategories(unwrap(result))).catch(() => {}) }, [])
  useEffect(() => { load() }, [])
  const active = Boolean(keyword || category || date)
  const pages = Math.max(1, Math.ceil(events.length / PAGE_SIZE))
  const visible = useMemo(() => events.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE), [events, page])
  const submit = (event) => { event.preventDefault(); setSearchParams({ ...(keyword && { q: keyword }), ...(category && { id_kategori: category }), ...(date && { tanggal_mulai: date }) }); load() }
  return <main className="event-exploration-page"><PageHeader title="Jelajah Event" subtitle="Temukan event dari organisasi lain sesuai jangkauan publikasinya." breadcrumb={['Ormavers', 'Jelajah Event']} icon="compass" /><form className="exploration-filters" onSubmit={submit}><label className="exploration-search"><AdminIcon name="search" size={17} /><input value={keyword} onChange={(event) => setKeyword(event.target.value)} placeholder="Cari judul atau deskripsi event..." aria-label="Cari judul atau deskripsi event" /></label><CategorySelect value={category} categories={categories} onChange={(event) => setCategory(event.target.value)} /><DatePicker value={date} onChange={(event) => setDate(event.target.value)} /><button className="exploration-primary-button" type="submit"><AdminIcon name="search" size={16} /> Cari</button></form>{loading ? <Skeletons /> : error ? <ErrorState retry={load} /> : events.length === 0 ? <EmptyState filtered={active} /> : <><div className="exploration-grid">{visible.map((event) => <EventCard event={event} key={event.id} />)}</div>{pages > 1 && <div className="exploration-pagination"><span>Halaman {page} dari {pages}</span><div><button className="exploration-pagination__button" disabled={page === 1} onClick={() => setPage((current) => current - 1)}>Sebelumnya</button>{Array.from({ length: pages }, (_, index) => index + 1).slice(0, 5).map((number) => <button className={`exploration-pagination__button${number === page ? ' is-current' : ''}`} key={number} onClick={() => setPage(number)}>{number}</button>)}<button className="exploration-pagination__button" disabled={page === pages} onClick={() => setPage((current) => current + 1)}>Berikutnya</button></div></div>}</>}</main>
}
