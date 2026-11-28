/* eslint-disable react-hooks/set-state-in-effect, react-hooks/exhaustive-deps */
import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import Swal from 'sweetalert2'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import ImageWithFallback from '../../components/ImageWithFallback.jsx'
import PageHeader from '../../components/PageHeader.jsx'
import InviteOrganizationsModal from '../../components/event/InviteOrganizationsModal.jsx'
import ConfirmDeleteEventModal from '../../components/event/ConfirmDeleteEventModal.jsx'
import { assetUrl } from '../../api/api.js'
import { deleteEvent, getEventDetail } from '../../api/eventManagement.js'
import { cancelCollaboration, cancelInvitation, getEventInvitations } from '../../api/eventInvitation.js'
import { createEventSharelink } from '../../api/eventExploration.js'
import { getMyRegistrationStatus } from '../../services/registrationStatusApi.js'
import '../../styles/event-management.css'
import '../../styles/event-detail-management.css'

const STATUS_LABEL = {
  menunggu: 'Menunggu',
  diterima: 'Diterima',
  ditolak: 'Ditolak',
  dibatalkan: 'Dibatalkan',
}

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

function formatDate(value) {
  if (!value) return '-'
  const [date] = String(value).split(/[ T]/)
  const [year, month, day] = date.split('-').map(Number)
  return year && month && day ? `${day} ${MONTHS[month - 1]} ${year}` : value
}

function formatDateTime(value) {
  const base = formatDate(value)
  if (!value || base === '-') return base
  const time = String(value).split(/[ T]/)[1]
  if (!time) return base
  return `${base}, ${time.slice(0, 5).replace(':', '.')} WIB`
}

function posterUrl(poster) {
  const raw = poster?.url || poster?.path_file
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(raw)) return assetUrl(raw)
  return assetUrl(String(raw).startsWith('/storage/') ? raw : `/storage/${String(raw).replace(/^\/+/, '')}`)
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

function unwrap(result) {
  return result?.data?.data || result?.data || null
}

function statusLabel(event) {
  return event.status_pendaftaran || 'Belum Dibuka'
}

function getOrgInitials(name) {
  if (!name) return '?'
  return name.split(/\s+/).map((n) => n[0]).join('').slice(0, 2).toUpperCase()
}

function errorMessage(error, fallback) {
  return error.response?.data?.message || fallback
}

function OrganizationEventDetailPage() {
  const { eventId } = useParams()
  const navigate = useNavigate()
  const [event, setEvent] = useState(null)
  const [universitasPembuat, setUniversitasPembuat] = useState(null)
  const [organisasiPembuat, setOrganisasiPembuat] = useState(null)
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [modalJenis, setModalJenis] = useState(null)
  const [cancellingId, setCancellingId] = useState(null)
  const [showDeleteModal, setShowDeleteModal] = useState(false)
  const [showPosterPreview, setShowPosterPreview] = useState(false)
  const [activePosterIndex, setActivePosterIndex] = useState(0)
  const [posterDirection, setPosterDirection] = useState(null)
  const [deleting, setDeleting] = useState(false)

  useEffect(() => {
    let active = true
    getMyRegistrationStatus().then((result) => {
      if (!active) return
      const organisasi = result?.data?.organisasiDetail
      setUniversitasPembuat(organisasi?.id_universitas ?? null)
      setOrganisasiPembuat(organisasi?.id ?? organisasi?.id_organisasi ?? null)
    })
    return () => { active = false }
  }, [])

  const load = async () => {
    setLoading(true)
    setError('')
    try {
      const [detailResult, invitationsResult] = await Promise.all([
        getEventDetail(eventId),
        getEventInvitations(eventId),
      ])
      const detail = unwrap(detailResult)
      if (detail?.peran_organisasi && detail.peran_organisasi !== 'pembuat') {
        throw Object.assign(new Error('Akses ditolak'), { response: { status: 403 } })
      }
      setEvent(detail)
      setActivePosterIndex(0)
      setItems(Array.isArray(invitationsResult?.data) ? invitationsResult.data : [])
    } catch (requestError) {
      setError(
        requestError.response?.status === 403
          ? 'Event ini hanya dapat dikelola oleh akun organisasi pembuatnya.'
          : errorMessage(requestError, 'Detail event gagal dimuat.')
      )
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [eventId])

  const counts = useMemo(
    () =>
      items.reduce(
        (acc, item) => {
          if (item.status === 'diterima') acc.diterima += 1
          if (item.status === 'menunggu') acc.menunggu += 1
          if (item.status === 'ditolak') acc.ditolak += 1
          return acc
        },
        { diterima: 0, menunggu: 0, ditolak: 0 }
      ),
    [items]
  )

  const handleCancel = async (item) => {
    const confirmation = await Swal.fire({
      icon: 'warning',
      title: 'Batalkan?',
      text: `${item.jenis === 'kolaborator' ? 'Ajakan kolaborasi' : 'Undangan'} yang belum dijawab akan dibatalkan.`,
      showCancelButton: true,
      confirmButtonText: 'Batalkan',
      cancelButtonText: 'Tutup',
      reverseButtons: true,
      confirmButtonColor: '#d03050',
      cancelButtonColor: '#64748b',
    })
    if (!confirmation.isConfirmed) return
    setCancellingId(item.id)
    try {
      const fn = item.jenis === 'undangan' ? cancelInvitation : cancelCollaboration
      await fn(eventId, item.id)
      await load()
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Ajakan/undangan berhasil dibatalkan.',
        confirmButtonColor: '#2563eb',
      })
    } catch (requestError) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: errorMessage(requestError, 'Gagal membatalkan.'),
        confirmButtonColor: '#2563eb',
      })
    } finally {
      setCancellingId(null)
    }
  }

  const handleDelete = async () => {
    setDeleting(true)
    try {
      const result = await deleteEvent(eventId)
      setShowDeleteModal(false)
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: result?.message ?? 'Event berhasil dihapus.',
        confirmButtonColor: '#2563eb',
      })
      navigate('/verifikasi/kelola-event')
    } catch (requestError) {
      await Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: errorMessage(requestError, 'Event gagal dihapus.'),
        confirmButtonColor: '#2563eb',
      })
    } finally {
      setDeleting(false)
    }
  }

  const handleShare = async () => {
    try {
      await createEventSharelink(eventId)
      const shareUrl = `${window.location.origin}/jelajah-event/${eventId}`
      await navigator.clipboard.writeText(shareUrl)
      await Swal.fire({
        icon: 'success',
        title: 'Tautan Tersalin!',
        text: 'Tautan publik event telah disalin ke clipboard.',
        confirmButtonColor: '#2563eb',
      })
    } catch {
      const shareUrl = `${window.location.origin}/jelajah-event/${eventId}`
      await Swal.fire({
        icon: 'info',
        title: 'Bagikan Event',
        html: `<p style="margin-bottom:8px">Salin tautan event berikut:</p><input type="text" readonly value="${shareUrl}" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;" onclick="this.select()" />`,
        confirmButtonColor: '#2563eb',
      })
    }
  }

  if (loading) {
    return (
      <main className="event-management-page">
        <div className="event-state event-state--loading">Memuat detail event…</div>
      </main>
    )
  }

  if (error || !event) {
    return (
      <main className="event-management-page">
        <div className="event-state event-state--error" role="alert">
          {error || 'Event tidak ditemukan.'}
          <br />
          <button
            type="button"
            className="event-primary-button"
            style={{ marginTop: 16 }}
            onClick={() => navigate('/verifikasi/kelola-event')}
          >
            Kembali ke Kelola Event
          </button>
        </div>
      </main>
    )
  }

  const posters = Array.isArray(event.posters) ? event.posters : []
  const poster = event.poster_utama || posters.find((item) => item.is_utama) || posters[0]
  const allPosters = poster
    ? [poster, ...posters.filter((item) => item !== poster && (poster.id == null || item.id !== poster.id))]
    : []
  const currentPoster = allPosters[activePosterIndex] || poster
  const kategori = event.nama_kategori || event.kategori?.nama_kategori || event.tag || 'Lainnya'

  return (
  <main className="event-management-page event-detail-management-page">
    <PageHeader
      title="Detail Event"
      subtitle="Kelola informasi event serta organisasi yang terlibat."
      breadcrumb={['Ormavers', 'Kelola Event', 'Detail Event']}
      icon="calendar"
      backTo="/verifikasi/kelola-event"
    />

      {/* Card Informasi Event */}
      <section className="event-detail-management-card card">
        <div className="event-detail-management-gallery">
          <div className="event-detail-management-poster">
            {posterUrl(currentPoster) ? (
              <button
                key={activePosterIndex}
                type="button"
                className={`event-detail-management-poster-trigger${posterDirection ? ` is-slide-${posterDirection}` : ''}`}
                onClick={() => setShowPosterPreview(true)}
                aria-label="Lihat poster event dalam ukuran besar"
              >
                <ImageWithFallback
                  src={posterUrl(currentPoster)}
                  alt={`${event.judul} - Poster ${activePosterIndex + 1}`}
                  style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                />
              </button>
            ) : (
              <span style={{ color: '#94a3b8' }}>
                <AdminIcon name="image" size={38} />
              </span>
            )}
          </div>

          {allPosters.length > 1 && (
            <div className="event-detail-management-gallery-controls">
              <button
                type="button"
                disabled={activePosterIndex === 0}
                onClick={() => {
                  setPosterDirection('prev')
                  setActivePosterIndex((index) => index - 1)
                }}
              >
                ‹ Sebelumnya
              </button>

              <div className="event-detail-management-gallery-dots">
                {allPosters.map((item, index) => (
                  <button
                    key={item.id || index}
                    type="button"
                    className={index === activePosterIndex ? 'is-current' : ''}
                    onClick={() => {
                      setPosterDirection(null)
                      setActivePosterIndex(index)
                    }}
                    aria-label={`Poster ${index + 1}`}
                  />
                ))}
              </div>

              <button
                type="button"
                disabled={activePosterIndex === allPosters.length - 1}
                onClick={() => {
                  setPosterDirection('next')
                  setActivePosterIndex((index) => index + 1)
                }}
              >
                Berikutnya ›
              </button>
            </div>
          )}
        </div>

        <div className="event-detail-management-info">
          <div className="event-card__pills">
            <span className="event-card__pill is-category">{kategori}</span>
            <span className="event-card__pill is-period">
              {event.jangkauan_publikasi ? event.jangkauan_publikasi.replaceAll('_', '-') : '-'}
            </span>
            <span className="event-card__pill is-status is-open">
              <span className="event-card__pill-dot" aria-hidden="true" /> {statusLabel(event)}
            </span>
            <span className="event-card__pill is-role is-owner">Pembuat</span>
          </div>

          <h1>{event.judul}</h1>

          {event.deskripsi && (
            <p style={{ margin: '0 0 16px', color: '#475569', fontSize: 14, lineHeight: 1.6 }}>
              {event.deskripsi}
            </p>
          )}

          <div className="event-detail-management-info-box">
            <p>
              <AdminIcon name="calendar" size={16} />
              <span>
                <strong>Tanggal:</strong> {formatDateTime(event.waktu_event || event.tanggal_event)}
              </span>
            </p>
            <p>
              <AdminIcon name="clock" size={16} />
              <span>
                <strong>Periode pendaftaran:</strong> {formatDateTime(event.periode_mulai)} –{' '}
                {formatDateTime(event.periode_selesai)}
              </span>
            </p>
            {event.tautan_pendaftaran && (
              <p>
                <AdminIcon name="link" size={16} />
                <span>
                  <strong>Tautan pendaftaran:</strong>{' '}
                  <a href={event.tautan_pendaftaran} target="_blank" rel="noreferrer">
                    Buka tautan pendaftaran
                  </a>
                </span>
              </p>
            )}
          </div>

          <div className="event-detail-management-actions">
            <Link className="event-card__btn is-neutral" to={`/verifikasi/kelola-event/${event.id}/edit`}>
              <AdminIcon name="edit" size={16} /> Ubah
            </Link>
            <button
              type="button"
              className="event-card__btn is-danger event-detail-action--delete"
              onClick={() => setShowDeleteModal(true)}
            >
              <AdminIcon name="trash" size={16} /> Hapus
            </button>
            <button
              type="button"
              className="event-card__btn is-primary"
              onClick={handleShare}
            >
              <AdminIcon name="share" size={16} /> Bagikan
            </button>
          </div>
        </div>
      </section>

      {/* Card Kolaborator & Undangan */}
      <section className="event-collaboration-management card">
        <header>
          <div>
            <h2>Kolaborator &amp; Undangan</h2>
            <p>Hanya terlihat oleh pembuat event</p>
            <div className="event-collaboration-summary">
              <span className="is-diterima">{counts.diterima} diterima</span>
              <span className="is-menunggu">{counts.menunggu} menunggu</span>
              <span className="is-ditolak">{counts.ditolak} ditolak</span>
            </div>
          </div>
          <button
            type="button"
            className="event-primary-button"
            onClick={() => setModalJenis('kolaborator')}
            disabled={event?.jangkauan_publikasi === 'internal'}
            title={event?.jangkauan_publikasi === 'internal'
              ? 'Event internal tidak dapat melibatkan organisasi lain.'
              : undefined}
          >
            <AdminIcon name="user-plus" size={16} /> Libatkan organisasi lain
          </button>
        </header>

        <div className="event-collaboration-table-wrap">
          <table className="event-collaboration-table">
            <thead>
              <tr>
                <th>Organisasi</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Keterangan / Aksi</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => {
                const org = item.tujuan || item.organisasi_tujuan
                const orgName = org?.nama_organisasi ?? '—'
                const orgLogo = org?.logo
                return (
                  <tr key={item.id}>
                    <td>
                      <div className="event-collaboration-org">
                        <div className="event-collaboration-org-logo">
                          {orgLogo ? (
                            <ImageWithFallback
                              src={resolveOrgLogo(orgLogo)}
                              alt={orgName}
                              fallback={<span>{getOrgInitials(orgName)}</span>}
                              style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                            />
                          ) : (
                            <span>{getOrgInitials(orgName)}</span>
                          )}
                        </div>
                        <strong>{orgName}</strong>
                      </div>
                    </td>
                    <td>
                      <span className={`event-collaboration-type is-${item.jenis}`}>
                        {item.jenis === 'kolaborator' ? 'Kolaborator' : 'Undangan'}
                      </span>
                    </td>
                    <td>
                      <span className={`event-collaboration-status is-${item.status}`}>
                        {STATUS_LABEL[item.status] ?? item.status}
                      </span>
                    </td>
                    <td>
                      {item.status === 'ditolak' && (
                        <span style={{ color: '#991b1b', fontSize: 13 }}>
                          Alasan: {item.alasan_penolakan || 'Tidak ada alasan'}
                        </span>
                      )}
                      {item.status === 'menunggu' && (
                        <button
                          type="button"
                          className="event-table-action-cancel"
                          disabled={cancellingId === item.id}
                          onClick={() => handleCancel(item)}
                        >
                          <AdminIcon name="close" size={12} />
                          {cancellingId === item.id
                            ? 'Membatalkan…'
                            : `Batalkan ${item.jenis === 'kolaborator' ? 'ajakan' : 'undangan'}`}
                        </button>
                      )}
                      {item.status === 'diterima' && (
                        <span style={{ color: '#166534', fontSize: 13 }}>
                          {item.jenis === 'kolaborator'
                            ? 'Organisasi resmi menjadi kolaborator'
                            : 'Event tampil di Jelajah Event anggotanya'}
                        </span>
                      )}
                      {item.status === 'dibatalkan' && (
                        <span style={{ color: '#64748b', fontSize: 13 }}>
                          Telah dibatalkan
                        </span>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
          {items.length === 0 && (
            <p className="event-participants__state" style={{ padding: 24, textAlign: 'center' }}>
              Belum ada organisasi yang dilibatkan.
            </p>
          )}
        </div>
      </section>

      {/* Preview Poster Event */}
      {showPosterPreview && posterUrl(currentPoster) && (
        <div
          className="event-poster-preview-overlay"
          onClick={() => setShowPosterPreview(false)}
        >
          <div
            className="event-poster-preview-content"
            role="dialog"
            aria-modal="true"
            aria-label="Preview poster event"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              type="button"
              className="event-poster-preview-close"
              onClick={() => setShowPosterPreview(false)}
              aria-label="Tutup preview poster"
            >
              <AdminIcon name="close" size={22} />
            </button>
            <img src={posterUrl(currentPoster)} alt={event.judul} />
          </div>
        </div>
      )}

      {/* Modal Libatkan Organisasi */}
      <InviteOrganizationsModal
        open={modalJenis !== null}
        jenis={modalJenis || 'kolaborator'}
        eventId={eventId}
        jangkauan={event?.jangkauan_publikasi}
        universitasPembuat={universitasPembuat}
        organisasiPembuat={organisasiPembuat}
        onClose={() => setModalJenis(null)}
        onDone={async (msg) => {
          setModalJenis(null)
          await load()
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: msg ?? 'Organisasi berhasil dilibatkan.',
            confirmButtonColor: '#2563eb',
          })
        }}
      />

      {/* Modal Konfirmasi Hapus Event */}
      <ConfirmDeleteEventModal
        open={showDeleteModal}
        deleting={deleting}
        onCancel={() => {
          if (!deleting) setShowDeleteModal(false)
        }}
        onConfirm={handleDelete}
      />
    </main>
  )
}

export default OrganizationEventDetailPage
