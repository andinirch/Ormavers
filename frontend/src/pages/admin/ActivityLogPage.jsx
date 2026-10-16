import { useEffect, useMemo, useRef, useState } from 'react'
import { PageIntro } from './DashboardPage.jsx'
import { getActivities } from '../../api/adminPanel.js'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import '../../styles/activity-log.css'

function ActivityLogPage() {
  const [items, setItems] = useState([])
  const [query, setQuery] = useState('')
  const [type, setType] = useState('')
  const [fromDate, setFromDate] = useState('')
  const [toDate, setToDate] = useState('')
  const [applied, setApplied] = useState({})
  const [error, setError] = useState('')
  const fromDateRef = useRef(null)
  const toDateRef = useRef(null)

  useEffect(() => {
    queueMicrotask(async () => {
      try {
        const result = await getActivities()
        setItems(result.data?.activities ?? [])
      } catch (requestError) {
        setError(requestError.response?.data?.message ?? 'Log aktivitas tidak dapat dimuat.')
      }
    })
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
  const formatDate = (value) => value ? value.split('-').reverse().join('/') : 'Pilih tanggal'
  const formatTime = (value) => value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—'
  const openDatePicker = (ref) => {
    if (ref.current?.showPicker) ref.current.showPicker()
    else ref.current?.click()
  }
  const apply = () => setApplied({ query, type, fromDate, toDate })
  const reset = () => { setQuery(''); setType(''); setFromDate(''); setToDate(''); setApplied({}) }

  return <main className="admin-page activity-log-page">
    <PageIntro eyebrow="AUDIT TRAIL & KEAMANAN • SUPER ADMIN" title="Log Aktivitas" description="Riwayat aktivitas yang dirangkum dari pendaftaran, verifikasi, akun, dan sesi login." />
    {error && <p className="admin-form-error">{error}</p>}
    <section className="admin-filter-card admin-activity-filter">
      <label className="admin-activity-field"><span>Pengguna</span><span className="admin-activity-control"><AdminIcon name="users" /><select value={query} onChange={(event) => setQuery(event.target.value)}><option value="">Semua pengguna</option>{users.map((user) => <option key={user} value={user}>{user}</option>)}</select><span className="admin-select-chevron">⌄</span></span></label>
      <label className="admin-activity-field"><span>Jenis aktivitas</span><span className="admin-activity-control"><AdminIcon name="filter" /><select value={type} onChange={(event) => setType(event.target.value)}><option value="">Semua jenis</option>{types.map((item) => <option key={item} value={item}>{item}</option>)}</select><span className="admin-select-chevron">⌄</span></span></label>
      <label className="admin-activity-field activity-date-field"><span>Dari tanggal</span><span className="admin-activity-control activity-date-control"><AdminIcon name="calendar" /><button type="button" className={`activity-date-button${fromDate ? ' has-value' : ''}`} onClick={() => openDatePicker(fromDateRef)}>{formatDate(fromDate)}</button><input className="activity-date-input" type="date" ref={fromDateRef} value={fromDate} aria-label="Dari tanggal" onChange={(event) => setFromDate(event.target.value)} /></span></label>
      <label className="admin-activity-field activity-date-field"><span>Sampai tanggal</span><span className="admin-activity-control activity-date-control"><AdminIcon name="calendar" /><button type="button" className={`activity-date-button${toDate ? ' has-value' : ''}`} onClick={() => openDatePicker(toDateRef)}>{formatDate(toDate)}</button><input className="activity-date-input" type="date" ref={toDateRef} value={toDate} aria-label="Sampai tanggal" onChange={(event) => setToDate(event.target.value)} /></span></label>
      <div className="admin-filter-actions"><button className="admin-button admin-button--muted" onClick={reset}>↻ Reset</button><button className="admin-button admin-users-apply" onClick={apply}><AdminIcon name="filter" size={16} />Terapkan</button></div>
    </section>
    <section className="admin-panel"><div className="admin-panel-heading"><div><h2>Aktivitas Terbaru</h2><p>{activities.length} aktivitas ditemukan</p></div></div>{activities.length === 0 ? <div className="admin-empty"><strong>Belum ada aktivitas tercatat</strong><span>Aktivitas akan muncul setelah ada pendaftaran, verifikasi, akun, atau login.</span></div> : <div className="admin-activity-preview">{activities.map((item) => <div className="admin-activity-row" key={item.id}><span className="admin-avatar admin-avatar--soft">{item.pengguna?.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase()}</span><span><strong>{item.tipe}</strong> {item.teks}</span><span className="admin-activity-time">{formatTime(item.waktu)}</span></div>)}</div>}</section>
  </main>
}

export default ActivityLogPage
