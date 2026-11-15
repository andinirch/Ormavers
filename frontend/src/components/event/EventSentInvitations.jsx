import { useEffect, useState } from 'react'
import Swal from 'sweetalert2'
import AdminIcon from '../admin/AdminIcon.jsx'
import { assetUrl } from '../../api/api.js'
import { cancelCollaboration, cancelInvitation, getEventInvitations } from '../../api/eventInvitation.js'

const STATUS_LABEL = {
  menunggu: 'Menunggu',
  diterima: 'Diterima',
  ditolak: 'Ditolak',
  dibatalkan: 'Dibatalkan',
}

function resolveOrgLogo(logo) {
  if (!logo) return ''
  const raw = String(logo).trim()
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(raw) || raw.startsWith('/storage/')) {
    return assetUrl(raw)
  }
  return assetUrl(`/storage/${raw.replace(/^\/+/, '')}`)
}

function getOrgInitials(name) {
  if (!name) return '?'
  return name.split(/\s+/).map((n) => n[0]).join('').slice(0, 2).toUpperCase()
}

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const first = errors ? Object.values(errors).flat()[0] : null
  return first ?? error.response?.data?.message ?? fallback
}

// Ajakan & undangan yang sudah dikirim pembuat event, lengkap dengan status dan pembatalan pending.
function EventSentInvitations({ eventId }) {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [cancellingId, setCancellingId] = useState(null)

  const load = async () => {
    setLoading(true)
    setError('')
    try {
      const result = await getEventInvitations(eventId)
      setItems(Array.isArray(result?.data) ? result.data : [])
    } catch (requestError) {
      setError(errorMessage(requestError, 'Data ajakan/undangan terkirim gagal dimuat.'))
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    queueMicrotask(() => { load() })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [eventId])

  const cancel = async (item) => {
    const label = item.jenis === 'kolaborator' ? 'Ajakan kolaborasi' : 'Undangan'
    const confirmed = await Swal.fire({
      icon: 'warning',
      title: 'Batalkan?',
      text: `${label} ke ${item.tujuan?.nama_organisasi ?? 'organisasi ini'} yang belum dijawab akan dibatalkan.`,
      showCancelButton: true,
      confirmButtonText: 'Batalkan',
      cancelButtonText: 'Tutup',
      reverseButtons: true,
      confirmButtonColor: '#d03050',
      cancelButtonColor: '#64748b',
    })
    if (!confirmed.isConfirmed) return
    setCancellingId(item.id)
    try {
      const fn = item.jenis === 'undangan' ? cancelInvitation : cancelCollaboration
      await fn(eventId, item.id)
      await load()
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: `${label} berhasil dibatalkan.`, confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      await Swal.fire({ icon: 'error', title: 'Gagal', text: errorMessage(requestError, 'Gagal membatalkan.'), confirmButtonColor: '#2563eb' })
    } finally {
      setCancellingId(null)
    }
  }

  return (
    <div className="event-sent">
      <div className="event-sent__header">
        <strong className="event-sent__title">Ajakan &amp; Undangan terkirim</strong>
        <span className="event-sent__badge">{items.length} organisasi</span>
      </div>

      {loading && <p className="event-sent__state">Memuat…</p>}
      {!loading && error && <p className="event-sent__state is-error" role="alert">{error}</p>}
      {!loading && !error && items.length === 0 && (
        <p className="event-sent__state">Belum ada organisasi yang dilibatkan dalam event ini.</p>
      )}

      {!loading && !error && items.length > 0 && (
        <ul className="event-sent__list">
          {items.map((item) => {
            const org = item.tujuan || item.organisasi_tujuan
            const orgName = org?.nama_organisasi ?? '—'
            const orgLogo = org?.logo
            return (
              <li key={item.id} className="event-sent__item">
                <div className="event-sent__org">
                  <div className="event-sent__logo">
                    {orgLogo ? (
                      <img src={resolveOrgLogo(orgLogo)} alt={orgName} />
                    ) : (
                      <span>{getOrgInitials(orgName)}</span>
                    )}
                  </div>
                  <div className="event-sent__info">
                    <span className="event-sent__name">{orgName}</span>
                    {item.status === 'ditolak' && item.alasan_penolakan && (
                      <small className="event-sent__rejection">
                        Alasan ditolak: {item.alasan_penolakan}
                      </small>
                    )}
                  </div>
                </div>

                <div className="event-sent__meta">
                  <span className={`event-sent__type is-${item.jenis}`}>
                    {item.jenis === 'kolaborator' ? 'Kolaborator' : 'Undangan'}
                  </span>
                  <span className={`event-sent__status is-${item.status}`}>
                    {STATUS_LABEL[item.status] ?? item.status}
                  </span>
                  {item.status === 'menunggu' && (
                    <button
                      type="button"
                      className="event-sent__btn-cancel"
                      onClick={() => cancel(item)}
                      disabled={cancellingId === item.id}
                      title={`Batalkan ${item.jenis === 'kolaborator' ? 'ajakan' : 'undangan'}`}
                    >
                      <AdminIcon name="close" size={13} />
                      {cancellingId === item.id ? 'Membatalkan…' : 'Batalkan'}
                    </button>
                  )}
                </div>
              </li>
            )
          })}
        </ul>
      )}
    </div>
  )
}

export default EventSentInvitations
