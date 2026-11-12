import { Link } from 'react-router-dom'
import AdminIcon from '../admin/AdminIcon.jsx'
import ImageWithFallback from '../ImageWithFallback.jsx'
import { assetUrl } from '../../api/api.js'

const BULAN_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

// Poster dapat dikirim sebagai `url` (Jelajah/Detail) atau `path_file` (Event Saya).
// `path_file` adalah path relatif disk "public", jadi perlu digabung ke /storage/.
// Format final masih menunggu konfirmasi backend (lihat catatan PRD 4.13).
function resolvePoster(poster) {
  if (!poster) return ''
  const raw = poster.url || poster.path_file || ''
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(raw) || raw.startsWith('/storage/')) {
    return assetUrl(raw)
  }
  return assetUrl(`/storage/${String(raw).replace(/^\/+/, '')}`)
}

function formatShort(value, withYear = true) {
  if (!value) return ''
  const [datePart] = String(value).split(/[ T]/)
  const [year, month, day] = datePart.split('-').map(Number)
  if (!year || !month || !day) return ''
  const mon = BULAN_ID[month - 1] ?? ''
  return withYear ? `${day} ${mon} ${year}` : `${day} ${mon}`
}

function parseDateOnly(value) {
  if (!value) return null
  const [datePart] = String(value).split(/[ T]/)
  const [year, month, day] = datePart.split('-').map(Number)
  if (!year || !month || !day) return null
  return new Date(year, month - 1, day)
}

// Status event diturunkan dari tanggal_event + status_pendaftaran.
function resolveEventStatus(event) {
  const eventDate = parseDateOnly(event.tanggalEvent)
  if (eventDate) {
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    if (eventDate.getTime() < today.getTime()) return { key: 'selesai', label: 'Selesai', cls: 'is-done' }
    if (eventDate.getTime() === today.getTime()) return { key: 'berlangsung', label: 'Berlangsung', cls: 'is-live' }
  }
  if (event.statusPendaftaran === 'Dibuka') return { key: 'dibuka', label: 'Pendaftaran: Dibuka', cls: 'is-open' }
  if (event.statusPendaftaran === 'Ditutup') return { key: 'ditutup', label: 'Pendaftaran: Ditutup', cls: 'is-closed' }
  return { key: 'belum', label: 'Pendaftaran: Belum Dibuka', cls: 'is-soon' }
}

function EventCard({
  event,
  onDelete,
  deleting,
  portfolioEligible = false,
  sudahMasukPortofolio = false,
  onAddToPortfolio,
  detailTo,
}) {
  const poster = resolvePoster(event.poster)
  const kategori = event.namaKategori || event.tag || 'Tanpa kategori'
  const isPembuat = event.peran === 'pembuat'
  const tanggal = formatShort(event.tanggalEvent, true) || 'Tanggal tidak tersedia'
  const status = resolveEventStatus(event)
  const isSelesai = status.key === 'selesai'
  // Pembuat selalu boleh (event selesai); kolaborator hanya jika sudah diterima
  // (tercermin dari portfolioEligible yang berasal dari daftar events-selesai).
  const canAddPortfolio = isSelesai && (isPembuat || portfolioEligible)

  return (
    <article className="event-card card">
      <div className="event-card__poster">
        {poster
          ? <ImageWithFallback src={poster} alt={event.judul} style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
          : <span className="event-card__poster-fallback"><AdminIcon name="image" size={26} /></span>}
      </div>

      <div className="event-card__body">
        <div className="event-card__pills">
          <span className="event-card__pill is-category">{kategori}</span>
          <span className="event-card__pill is-period">
            <AdminIcon name="calendar" size={13} /> {tanggal}
          </span>
          <span className={`event-card__pill is-status ${status.cls}`}>
            <span className="event-card__pill-dot" aria-hidden="true" /> {status.label}
          </span>
          {!isPembuat && (
            <span className="event-card__pill is-role">Kolaborator</span>
          )}
        </div>

        <h3 className="event-card__title">{event.judul}</h3>
        {event.deskripsi && <p className="event-card__desc">{event.deskripsi}</p>}

        <div className="event-card__actions">
          <div className="event-card__actions-left">
            {detailTo ? <Link to={detailTo} className="event-card__btn is-neutral"><AdminIcon name="eye" size={16} /> Detail</Link> : <button type="button" className="event-card__btn is-neutral" disabled title="Detail event akan tersedia pada tahap berikutnya"><AdminIcon name="eye" size={16} /> Detail</button>}
            {isPembuat && (
              <Link to={`/verifikasi/kelola-event/${event.id}/edit`} className="event-card__btn is-neutral">
                <AdminIcon name="edit" size={16} /> Ubah
              </Link>
            )}
            {canAddPortfolio && (
              sudahMasukPortofolio ? (
                <span className="event-card__btn is-portfolio-done">
                  <AdminIcon name="check-circle" size={16} /> Ada di Portofolio
                </span>
              ) : (
                <button
                  type="button"
                  className="event-card__btn is-portfolio"
                  onClick={() => onAddToPortfolio?.(event)}
                  disabled={!portfolioEligible}
                  title={portfolioEligible ? 'Tambah ke Portofolio' : 'Data portofolio belum siap'}
                >
                  <AdminIcon name="folder-special" size={16} /> Tambah ke Portofolio
                </button>
              )
            )}
          </div>

          {isPembuat && (
            <div className="event-card__actions-right">
              <button
                type="button"
                className="event-card__btn is-danger"
                onClick={() => onDelete?.(event)}
                disabled={deleting}
                title="Hapus event"
              >
                <AdminIcon name="trash" size={16} /> {deleting ? 'Menghapus…' : 'Hapus'}
              </button>
            </div>
          )}
        </div>
      </div>
    </article>
  )
}

export default EventCard
