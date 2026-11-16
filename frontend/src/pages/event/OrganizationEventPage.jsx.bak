import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import Swal from 'sweetalert2'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import EventCard from '../../components/event/EventCard.jsx'
import AjakanUndanganPanel from '../../components/event/AjakanUndanganPanel.jsx'
import AddToPortfolioModal from '../../components/event/AddToPortfolioModal.jsx'
import ConfirmDeleteEventModal from '../../components/event/ConfirmDeleteEventModal.jsx'
import { deleteEvent, getMyEvents } from '../../api/eventManagement.js'
import { getFinishedEvents } from '../../api/eventPortfolio.js'
import '../../styles/event-management.css'

function toList(result) {
  const data = result?.data
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.event)) return data.event
  return []
}

function normalizeEvent(item) {
  return {
    id: item.id,
    judul: item.judul ?? '',
    deskripsi: item.deskripsi ?? '',
    tanggalEvent: item.tanggal_event ?? '',
    periodeMulai: item.periode_mulai ?? '',
    periodeSelesai: item.periode_selesai ?? '',
    namaKategori: item.nama_kategori ?? null,
    tag: item.tag ?? null,
    statusPendaftaran: item.status_pendaftaran ?? '',
    jangkauan: item.jangkauan_publikasi ?? '',
    peran: item.peran_organisasi ?? 'pembuat',
    poster: item.poster_utama ?? null,
  }
}

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const first = errors ? Object.values(errors).flat()[0] : null
  return first ?? error.response?.data?.message ?? fallback
}

function OrganizationEventPage() {
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [deletingId, setDeletingId] = useState(null)
  const [portfolioStatus, setPortfolioStatus] = useState({})
  const [portfolioTarget, setPortfolioTarget] = useState(null)
  const [deleteTarget, setDeleteTarget] = useState(null)

  useEffect(() => {
    let active = true
    const load = async () => {
      setLoading(true)
      setError('')
      try {
        const result = await getMyEvents()
        if (active) setEvents(toList(result).map(normalizeEvent))
      } catch (requestError) {
        if (active) setError(errorMessage(requestError, 'Event gagal dimuat.'))
      } finally {
        if (active) setLoading(false)
      }
    }
    queueMicrotask(load)
    return () => { active = false }
  }, [])

  useEffect(() => {
    let active = true
    getFinishedEvents()
      .then((result) => {
        if (!active) return
        const list = Array.isArray(result?.data) ? result.data : []
        const map = {}
        list.forEach((item) => {
          map[String(item.id)] = { eligible: true, sudahMasuk: Boolean(item.sudah_masuk_portofolio) }
        })
        setPortfolioStatus(map)
      })
      .catch(() => { /* biarkan tombol nonaktif bila gagal dimuat */ })
    return () => { active = false }
  }, [])

  const requestDelete = (event) => setDeleteTarget(event)

  const confirmDelete = async () => {
    if (!deleteTarget) return
    const target = deleteTarget
    setDeletingId(target.id)
    try {
      const result = await deleteEvent(target.id)
      setEvents((current) => current.filter((item) => item.id !== target.id))
      setDeleteTarget(null)
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: result?.message ?? 'Event berhasil dihapus.', confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      await Swal.fire({ icon: 'error', title: 'Gagal menghapus', text: errorMessage(requestError, 'Event gagal dihapus.'), confirmButtonColor: '#2563eb' })
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <main className="event-management-page">
      <PageHeader
        title="Kelola Event"
        subtitle="Daftar event yang dibuat dan diikuti organisasi kamu."
        breadcrumb={['Ormavers', 'Kelola Event']}
        icon="calendar"
      />

      <div className="event-management-layout">
        <section className="event-management-main" aria-label="Daftar event organisasi">
          <div className="event-management-toolbar">
            <span className="event-search">
              <AdminIcon name="search" size={16} />
              <input
                type="search"
                placeholder="Cari event"
                aria-label="Cari event"
                disabled
                title="Pencarian event akan tersedia pada tahap berikutnya"
              />
            </span>
            <Link to="/verifikasi/kelola-event/baru" className="event-primary-button event-primary-button--toolbar">
              <AdminIcon name="plus" size={16} /> Buat Event
            </Link>
          </div>

          {loading && (
            <div className="event-state event-state--loading" role="status" aria-live="polite">
              Memuat event…
            </div>
          )}

          {!loading && error && (
            <div className="event-state event-state--error" role="alert">{error}</div>
          )}

          {!loading && !error && events.length === 0 && (
            <div className="event-state event-state--empty">
              <span className="event-state__icon"><AdminIcon name="calendar" size={26} /></span>
              <strong>Belum ada event</strong>
              <p>Event yang kamu buat atau ikuti sebagai kolaborator akan tampil di sini.</p>
              <Link to="/verifikasi/kelola-event/baru" className="event-primary-button">
                <AdminIcon name="plus" size={16} /> Buat Event Pertama
              </Link>
            </div>
          )}

          {!loading && !error && events.length > 0 && (
            <div className="event-grid">
              {events.map((event) => (
                <EventCard
                  key={event.id}
                  event={event}
                  onDelete={requestDelete}
                  deleting={deletingId === event.id}
                  portfolioEligible={Boolean(portfolioStatus[String(event.id)]?.eligible)}
                  sudahMasukPortofolio={Boolean(portfolioStatus[String(event.id)]?.sudahMasuk)}
                  onAddToPortfolio={(target) => setPortfolioTarget(target)}
                  detailTo={
                    event.peran === 'pembuat'
                      ? `/verifikasi/kelola-event/${event.id}`
                      : `/jelajah-event/${event.id}?from=kelola-event`
                  }
                />
              ))}
            </div>
          )}
        </section>

        <AjakanUndanganPanel />
      </div>

      <AddToPortfolioModal
        open={portfolioTarget !== null}
        event={portfolioTarget}
        onClose={() => setPortfolioTarget(null)}
        onDone={() => {
          if (portfolioTarget) {
            setPortfolioStatus((current) => ({
              ...current,
              [String(portfolioTarget.id)]: { eligible: true, sudahMasuk: true },
            }))
            Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Event berhasil ditambahkan ke portofolio.', confirmButtonColor: '#2563eb' })
          }
          setPortfolioTarget(null)
        }}
      />

      <ConfirmDeleteEventModal
        open={deleteTarget !== null}
        deleting={deleteTarget !== null && deletingId === deleteTarget.id}
        onCancel={() => { if (deletingId === null) setDeleteTarget(null) }}
        onConfirm={confirmDelete}
      />
    </main>
  )
}

export default OrganizationEventPage
