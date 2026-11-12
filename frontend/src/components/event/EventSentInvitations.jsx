import { useEffect, useState } from 'react'
import Swal from 'sweetalert2'
import AdminIcon from '../admin/AdminIcon.jsx'
import { cancelCollaboration, cancelInvitation, getEventInvitations } from '../../api/eventInvitation.js'

const STATUS_LABEL = {
  menunggu: 'Menunggu',
  diterima: 'Diterima',
  ditolak: 'Ditolak',
  dibatalkan: 'Dibatalkan',
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
    const confirmed = await Swal.fire({
      icon: 'warning',
      title: 'Batalkan?',
      text: 'Ajakan/undangan yang belum dijawab akan dibatalkan.',
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
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Ajakan/undangan berhasil dibatalkan.', confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      await Swal.fire({ icon: 'error', title: 'Gagal', text: errorMessage(requestError, 'Gagal membatalkan.'), confirmButtonColor: '#2563eb' })
    } finally {
      setCancellingId(null)
    }
  }

  return (
    <div className="event-sent">
      <strong className="event-sent__title">Ajakan &amp; Undangan terkirim</strong>

      {loading && <p className="event-sent__state">Memuat…</p>}
      {!loading && error && <p className="event-sent__state is-error" role="alert">{error}</p>}
      {!loading && !error && items.length === 0 && (
        <p className="event-sent__state">Belum ada organisasi yang dilibatkan.</p>
      )}

      {!loading && !error && items.length > 0 && (
        <ul className="event-sent__list">
          {items.map((item) => (
            <li key={item.id}>
              <span className="event-sent__name">{item.tujuan?.nama_organisasi ?? '—'}</span>
              <span className={`event-sent__type is-${item.jenis}`}>
                {item.jenis === 'kolaborator' ? 'Kolaborator' : 'Undangan'}
              </span>
              <span className={`event-sent__status is-${item.status}`}>
                {STATUS_LABEL[item.status] ?? item.status}
              </span>
              {item.status === 'menunggu' && (
                <button type="button" onClick={() => cancel(item)} disabled={cancellingId === item.id}>
                  <AdminIcon name="close" size={13} /> Batalkan
                </button>
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}

export default EventSentInvitations
