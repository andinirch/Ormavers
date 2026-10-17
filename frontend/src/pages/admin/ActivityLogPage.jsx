import { useEffect, useMemo, useRef, useState } from 'react'
import { PageIntro } from './DashboardPage.jsx'
import { getActivities } from '../../api/adminPanel.js'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import '../../styles/activity-log.css'

const PAGE_SIZE = 8
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

const ACTIVITY_BADGES = {
  Login: 'login',
  Akun: 'akun',
  Role: 'role',
  Verifikasi: 'verifikasi',
  Pendaftaran: 'pendaftaran',
}

function avatarTone(name) {
  const score = [...(name || '')].reduce((total, character) => total + character.charCodeAt(0), 0)
  return (score % 5) + 1
}

function initials(name) {
  return name?.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() || 'P'
}

function formatDate(value) {
  return value ? value.split('-').reverse().join('/') : 'Pilih tanggal'
}

function formatActivityTime(value) {
  if (!value) return { date: '—', time: '' }
  const [date, time] = value.replace('T', ' ').split(' ')
  const [year, month, day] = (date || '').split('-')
  if (!year || !month || !day) return { date: value, time: '' }
  return {
    date: `${Number(day)} ${MONTHS[Number(month) - 1] || month} ${year}`,
    time: `${(time || '').slice(0, 5)} WIB`,
  }
}

function ActivityLogPage() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [query, setQuery] = useState('')
  const [type, setType] = useState('')
  const [fromDate, setFromDate] = useState('')
  const [toDate, setToDate] = useState('')
  const [applied, setApplied] = useState({})
  const [page, setPage] = useState(1)
  const [error, setError] = useState('')
  const fromDateRef = useRef(null)
  const toDateRef = useRef(null)

  useEffect(() => {
    const controller = new AbortController()

    const loadActivities = async () => {
      try {
        const result = await getActivities(controller.signal)
        setItems(result.data?.activities ?? [])
      } catch (requestError) {
        if (requestError.code !== 'ERR_CANCELED' && requestError.name !== 'CanceledError') {
          setError(requestError.response?.data?.message ?? 'Log aktivitas tidak dapat dimuat.')
        }
      } finally {
        if (!controller.signal.aborted) setLoading(false)
      }
    }

    loadActivities()
    return () => controller.abort()
  }, [])

  const users = [...new Set(items.map((item) => item.pengguna).filter(Boolean))]
  const types = [...new Set(items.map((item) => item.tipe).filter(Boolean))]
  const activities = useMemo(() => items.filter((item) => {
    const date = item.waktu?.slice(0, 10)
    return (!applied.query || item.pengguna === applied.query)
      && (!applied.type || item.tipe === applied.type)
      && (!applied.fromDate || date >= applied.fromDate)
      && (!applied.toDate || date <= applied.toDate)
  }), [items, applied])
  const totalPages = Math.max(1, Math.ceil(activities.length / PAGE_SIZE))
  const visibleActivities = activities.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE)
  const firstItem = activities.length ? (page - 1) * PAGE_SIZE + 1 : 0
  const lastItem = Math.min(page * PAGE_SIZE, activities.length)

  const openDatePicker = (ref) => {
    if (ref.current?.showPicker) ref.current.showPicker()
    else ref.current?.click()
  }
  const apply = () => { setApplied({ query, type, fromDate, toDate }); setPage(1) }
  const reset = () => { setQuery(''); setType(''); setFromDate(''); setToDate(''); setApplied({}); setPage(1) }
  const goToPage = (nextPage) => setPage(Math.min(Math.max(nextPage, 1), totalPages))

  return <main className="admin-page activity-log-page">
    <PageIntro eyebrow="AUDIT TRAIL & KEAMANAN • SUPER ADMIN" title="Log Aktivitas" description="Riwayat aktivitas yang dirangkum dari pendaftaran, verifikasi, akun, dan sesi login." />
    {error && <p className="admin-form-error">{error}</p>}
    <section className="admin-filter-card admin-activity-filter">
      <label className="admin-activity-field"><span>Pengguna</span><span className="admin-activity-control"><AdminIcon name="users" /><select value={query} onChange={(event) => setQuery(event.target.value)}><option value="">Semua pengguna</option>{users.map((user) => <option key={user} value={user}>{user}</option>)}</select><AdminIcon name="chevron-down" size={14} /></span></label>
      <label className="admin-activity-field"><span>Jenis aktivitas</span><span className="admin-activity-control"><AdminIcon name="filter-funnel" /><select value={type} onChange={(event) => setType(event.target.value)}><option value="">Semua jenis</option>{types.map((item) => <option key={item} value={item}>{item}</option>)}</select><AdminIcon name="chevron-down" size={14} /></span></label>
      <label className="admin-activity-field activity-date-field"><span>Dari tanggal</span><span className="admin-activity-control activity-date-control"><AdminIcon name="calendar" /><button type="button" className={`activity-date-button${fromDate ? ' has-value' : ''}`} onClick={() => openDatePicker(fromDateRef)}>{formatDate(fromDate)}</button><input className="activity-date-input" type="date" ref={fromDateRef} value={fromDate} aria-label="Dari tanggal" onChange={(event) => setFromDate(event.target.value)} /></span></label>
      <label className="admin-activity-field activity-date-field"><span>Sampai tanggal</span><span className="admin-activity-control activity-date-control"><AdminIcon name="calendar" /><button type="button" className={`activity-date-button${toDate ? ' has-value' : ''}`} onClick={() => openDatePicker(toDateRef)}>{formatDate(toDate)}</button><input className="activity-date-input" type="date" ref={toDateRef} value={toDate} aria-label="Sampai tanggal" onChange={(event) => setToDate(event.target.value)} /></span></label>
      <div className="admin-filter-actions"><button className="admin-button admin-users-apply" onClick={apply}><AdminIcon name="check" size={16} />Terapkan</button><button className="admin-button admin-button--muted" onClick={reset}>Reset</button></div>
    </section>
    <section className="admin-panel activity-log-panel">
      <div className="admin-panel-heading"><div><h2>Catatan Log Sistem</h2><p>Real-time trail audit aktivitas ekosistem Ormavers</p></div></div>
      {loading ? <><div className="admin-table-wrap activity-table-wrap"><table className="admin-activity-table"><colgroup><col className="activity-col-time" /><col className="activity-col-user" /><col className="activity-col-type" /><col className="activity-col-text" /></colgroup><thead><tr><th>WAKTU</th><th>PENGGUNA</th><th>JENIS</th><th>AKTIVITAS</th></tr></thead><tbody>{Array.from({ length: PAGE_SIZE }, (_, index) => <tr className="activity-loading-row" key={index}><td colSpan="4"><span>Memuat log aktivitas...</span></td></tr>)}</tbody></table></div><div className="activity-pagination activity-pagination--loading"><span>Memuat aktivitas...</span><div className="activity-pagination-controls"><button disabled aria-label="Memuat halaman sebelumnya"><AdminIcon name="chevron-left" size={14} /></button><button className="is-current" disabled>1</button><button disabled aria-label="Memuat halaman berikutnya"><AdminIcon name="chevron-right" size={14} /></button></div></div><div className="activity-security-note"><AdminIcon name="lock" size={16} /><span>Log aktivitas disimpan secara terenkripsi dan tidak dapat diubah oleh pengguna.</span></div></> : error ? null : activities.length === 0 && items.length > 0 ? <div className="admin-empty"><strong>Tidak ada aktivitas yang cocok dengan filter</strong><span>Coba ubah filter atau reset pencarian.</span></div> : activities.length === 0 ? <div className="admin-empty"><strong>Belum ada aktivitas tercatat</strong><span>Aktivitas akan muncul setelah ada pendaftaran, verifikasi, akun, atau login.</span></div> : <>
        <div className="admin-table-wrap activity-table-wrap"><table className="admin-activity-table"><colgroup><col className="activity-col-time" /><col className="activity-col-user" /><col className="activity-col-type" /><col className="activity-col-text" /></colgroup><thead><tr><th>WAKTU</th><th>PENGGUNA</th><th>JENIS</th><th>AKTIVITAS</th></tr></thead><tbody>{visibleActivities.map((item) => { const waktu = formatActivityTime(item.waktu); const badge = ACTIVITY_BADGES[item.tipe] || 'default'; return <tr key={item.id}><td className="activity-time-cell"><strong>{waktu.date}</strong><code>{waktu.time}</code></td><td><div className="activity-user-cell"><span className={`admin-avatar activity-avatar--tone-${avatarTone(item.pengguna)}`}>{initials(item.pengguna)}</span><span className="activity-user-name">{item.pengguna}</span></div></td><td><span className={`activity-badge activity-badge--${badge}`}><span className="activity-badge-dot" />{item.tipe}</span></td><td className="activity-text-cell">{item.teks}</td></tr> })}</tbody></table></div>
        <div className="activity-pagination"><span>Menampilkan <b>{firstItem}-{lastItem}</b> dari <b>{activities.length}</b> aktivitas</span><div className="activity-pagination-controls"><button aria-label="Halaman sebelumnya" disabled={page === 1} onClick={() => goToPage(page - 1)}><AdminIcon name="chevron-left" size={14} /></button>{Array.from({ length: Math.min(3, totalPages) }, (_, index) => index + 1).map((item) => <button key={item} className={page === item ? 'is-current' : ''} onClick={() => goToPage(item)}>{item}</button>)}{totalPages > 4 && <><span>…</span><button className={page === totalPages ? 'is-current' : ''} onClick={() => goToPage(totalPages)}>{totalPages}</button></>} {totalPages > 3 && totalPages <= 4 && <button className={page === totalPages ? 'is-current' : ''} onClick={() => goToPage(totalPages)}>{totalPages}</button>}<button aria-label="Halaman berikutnya" disabled={page === totalPages} onClick={() => goToPage(page + 1)}><AdminIcon name="chevron-right" size={14} /></button></div></div>
        <div className="activity-security-note"><AdminIcon name="lock" size={16} /><span>Log aktivitas disimpan secara terenkripsi dan tidak dapat diubah oleh pengguna.</span></div>
      </>}
    </section>
  </main>
}

export default ActivityLogPage
