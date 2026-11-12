/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import ImageWithFallback from '../../components/ImageWithFallback.jsx'
import { assetUrl } from '../../api/api.js'
import { createEventSharelink, getEventByShareToken, getExploredEvent } from '../../api/eventExploration.js'
import '../../styles/event-exploration.css'

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
const formatDate = (value) => { if (!value) return '-'; const [date] = String(value).split(/[ T]/); const [year, month, day] = date.split('-').map(Number); return year && month && day ? `${day} ${MONTHS[month - 1]} ${year}` : value }
function posterUrl(poster) { const raw = poster?.url || poster?.path_file || poster?.poster || poster?.image || poster?.thumbnail || poster?.cover_image || poster?.banner || poster?.foto || poster; if (!raw) return ''; if (/^(https?:|data:|blob:)/i.test(String(raw))) return assetUrl(String(raw)); return assetUrl(String(raw).startsWith('/storage/') ? String(raw) : `/storage/${String(raw).replace(/^\/+/, '')}`) }
const normalize = (result) => result?.data?.data || result?.data || null
function eventStatus(event) { const date = new Date(`${event?.tanggal_event}T00:00:00`); if (!Number.isNaN(date.getTime()) && date < new Date(new Date().toDateString())) return ['Selesai', 'is-done']; const value = event?.status_pendaftaran || 'Belum Dibuka'; return [value, value === 'Dibuka' ? 'is-open' : 'is-closed'] }
function LineIcon({ type }) { const paths = { calendar: <><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18" /></>, clock: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>, link: <><path d="M10 13a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1" /><path d="M14 11a5 5 0 0 0-7.1-.1l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1" /></>, flag: <><path d="M5 21V4" /><path d="M5 5c4-3 7 3 12 0v9c-5 3-8-3-12 0" /></>, share: <><circle cx="18" cy="5" r="2" /><circle cx="6" cy="12" r="2" /><circle cx="18" cy="19" r="2" /><path d="m8 11 8-5M8 13l8 5" /></> }; return <svg className="detail-line-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[type]}</svg> }
function PosterFallback() { return <div className="exploration-detail-poster-fallback"><AdminIcon name="image" size={42} /></div> }
function ErrorState({ notFound, retry, backTo, backLabel }) {
  return (
    <div className="exploration-state">
      <div className="exploration-state__illustration is-error">
        <AdminIcon name={notFound ? 'search' : 'alert-circle'} size={42} />
      </div>
      <h2>{notFound ? 'Event tidak dapat diakses.' : 'Gagal memuat detail event.'}</h2>
      <p>{notFound ? 'Event tidak ditemukan atau Anda tidak memiliki akses untuk melihatnya.' : 'Terjadi kesalahan saat mengambil data.'}</p>
      {retry && <button className="exploration-outline-button" onClick={retry}>Coba lagi</button>}
      <Link className="exploration-back" to={backTo || '/jelajah-event'}>
        {backLabel || '← Kembali ke Jelajah Event'}
      </Link>
    </div>
  )
}
function ShareModal({ onClose }) { const [copied, setCopied] = useState(false); const url = window.location.href; const copy = async () => { try { await navigator.clipboard.writeText(url) } catch { void 0 } setCopied(true); setTimeout(() => setCopied(false), 2000) }; return <div className="exploration-modal-backdrop" role="presentation" onClick={onClose}><div className="exploration-share-modal" role="dialog" aria-modal="true" aria-labelledby="share-title" onClick={(eventClick) => eventClick.stopPropagation()}><h2 id="share-title">Bagikan Event</h2><div className="exploration-share-input"><input value={url} readOnly aria-label="Tautan event" /><button onClick={copy}>{copied ? 'Tersalin!' : 'Salin tautan'}</button></div><p className="exploration-share-helper"><AdminIcon name="info" size={12} /> Tautan hanya dapat dibuka oleh pengguna yang sudah login dan terverifikasi.</p><div className="exploration-share-footer"><button className="exploration-share-close" onClick={onClose}>Tutup</button></div></div></div> }
function DetailContent({ event, onShare, backTo, backLabel }) {
  const [statusText, statusClass] = eventStatus(event); const posters = event.galeri_poster || event.posters || event.images || event.gallery || event.foto || (event.poster_utama ? [event.poster_utama] : []); const [active, setActive] = useState(0); const current = posters[active]; const collaborators = event.kolaborator || event.collaborators || event.partners || []; const registrationPeriod = event.periode_mulai && event.periode_selesai ? `${formatDate(event.periode_mulai)} – ${formatDate(event.periode_selesai)}` : formatDate(event.periode_mulai || event.periode_selesai)
  return <><Link className="exploration-back" to={backTo || '/jelajah-event'}>{backLabel || '← Kembali ke Jelajah Event'}</Link><div className="exploration-detail-layout"><div className="exploration-detail-left"><div className="exploration-gallery"><div className="exploration-gallery__main">{current ? <ImageWithFallback src={posterUrl(current)} alt={event.judul} fallback={<PosterFallback />} /> : <PosterFallback />}</div>{posters.length > 1 && <div className="exploration-gallery__controls"><button disabled={active === 0} onClick={() => setActive((index) => index - 1)}>‹ Sebelumnya</button><div className="exploration-gallery__dots">{posters.map((poster, index) => <button key={poster.id || index} className={index === active ? 'is-current' : ''} onClick={() => setActive(index)} aria-label={`Poster ${index + 1}`} />)}</div><button disabled={active === posters.length - 1} onClick={() => setActive((index) => index + 1)}>Berikutnya ›</button></div>}</div></div><aside className="exploration-detail-sidebar"><h1>{event.judul}</h1><p className="exploration-organizer">Diselenggarakan oleh <strong>{event.organisasi?.nama_organisasi || '-'}</strong></p><div className="exploration-pills"><span className="detail-pill">{event.nama_kategori || event.kategori?.nama_kategori || event.tag || 'Lainnya'}</span><span className="detail-pill">{event.jangkauan_publikasi?.replaceAll('_', '-') || '-'}</span><span className={`detail-pill detail-pill--status ${statusClass}`}>● {statusText === 'Dibuka' ? 'Pendaftaran: Dibuka' : statusText}</span></div><div className="exploration-info-box"><p><LineIcon type="calendar" /><span><strong>Tanggal:</strong> {formatDate(event.tanggal_event)}</span></p><p><LineIcon type="clock" /><span><strong>Periode pendaftaran:</strong> {registrationPeriod}</span></p><p><LineIcon type="link" /><span><strong>Tautan pendaftaran:</strong> <a href={event.tautan_pendaftaran} target="_blank" rel="noreferrer">Buka tautan</a></span></p></div>{collaborators.length > 0 && <section className="detail-collaborators"><h3>Kolaborator:</h3><div className="exploration-collaborators">{collaborators.map((item) => <span key={item.id_organisasi || item.id || item.nama_organisasi}>{item.nama_organisasi || item.name}</span>)}</div></section>}<div className="exploration-detail-actions"><button className="exploration-report-button"><LineIcon type="flag" /> Laporkan</button><button className="exploration-primary-button" onClick={onShare}><LineIcon type="share" /> Bagikan</button></div></aside><article className="exploration-description"><h2>Deskripsi</h2><p>{event.deskripsi || 'Deskripsi event belum tersedia.'}</p></article></div></>
}
export default function EventDetailPage({ shareToken = null }) {
  const { id } = useParams()
  const [searchParams] = useSearchParams()
  const fromSource = searchParams.get('from')
  const backTo = fromSource === 'kelola-event' ? '/verifikasi/kelola-event' : '/jelajah-event'
  const backLabel = fromSource === 'kelola-event' ? '← Kembali ke Kelola Event' : '← Kembali ke Jelajah Event'

  const [event, setEvent] = useState(null)
  const [state, setState] = useState('loading')
  const [shareOpen, setShareOpen] = useState(false)
  const load = async () => {
    setState('loading')
    try {
      const result = shareToken ? await getEventByShareToken(shareToken) : await getExploredEvent(id)
      setEvent(normalize(result))
      setState('ready')
    } catch (error) {
      setState(error.response?.status === 403 || error.response?.status === 404 ? 'not-found' : 'error')
    }
  }
  useEffect(() => {
    load()
  }, [id, shareToken])
  if (state === 'loading')
    return (
      <main className="event-exploration-page">
        <div className="exploration-detail-skeleton" />
        <div className="exploration-detail-skeleton exploration-detail-skeleton--short" />
      </main>
    )
  if (state !== 'ready')
    return (
      <main className="event-exploration-page">
        <ErrorState notFound={state === 'not-found'} retry={state === 'error' ? load : null} backTo={backTo} backLabel={backLabel} />
      </main>
    )
  return (
    <main className="event-exploration-page">
      <DetailContent
        event={event}
        backTo={backTo}
        backLabel={backLabel}
        onShare={async () => {
          try {
            await createEventSharelink(event.id)
          } catch {
            void 0
          }
          setShareOpen(true)
        }}
      />
      {shareOpen && <ShareModal event={event} onClose={() => setShareOpen(false)} />}
    </main>
  )
}
