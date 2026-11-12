import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import Swal from 'sweetalert2'
import AdminIcon from '../admin/AdminIcon.jsx'
import RejectInvitationModal from './RejectInvitationModal.jsx'
import { assetUrl } from '../../api/api.js'
import { answerInvitation, getIncomingInvitationDetail, getIncomingInvitations } from '../../api/eventInvitation.js'

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const first = errors ? Object.values(errors).flat()[0] : null
  return first ?? error.response?.data?.message ?? fallback
}

const JANGKAUAN_LABEL = {
  internal: 'Internal Kampus',
  satu_universitas: 'Satu Universitas',
  multi_universitas: 'Multi-universitas',
}

function resolvePoster(poster) {
  const raw = poster?.url || poster?.path_file || ''
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(raw) || raw.startsWith('/storage/')) return assetUrl(raw)
  return assetUrl(`/storage/${String(raw).replace(/^\/+/, '')}`)
}

function formatLongDate(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(date)
}

function AjakanDetailModal({ idAjakan, onClose, onDone }) {
  const [detail, setDetail] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [view, setView] = useState('detail')
  const [reason, setReason] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const onCloseRef = useRef(onClose)
  const submittingRef = useRef(submitting)
  const viewRef = useRef(view)
  const backRef = useRef(null)
  useEffect(() => { onCloseRef.current = onClose })
  useEffect(() => { submittingRef.current = submitting })
  useEffect(() => { viewRef.current = view })
  useEffect(() => {
    backRef.current = () => { setView('detail'); setReason(''); setError('') }
  })

  // Portal modal: kunci scroll body + tutup dengan Escape.
  useEffect(() => {
    if (!idAjakan) return undefined
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const onKeyDown = (event) => {
      if (event.key === 'Escape' && !submittingRef.current) {
        if (viewRef.current === 'reject') backRef.current?.()
        else onCloseRef.current?.()
      }
    }
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('keydown', onKeyDown)
      document.body.style.overflow = previousOverflow
    }
  }, [idAjakan])

  useEffect(() => {
    if (!idAjakan) return undefined
    let active = true
    const run = async () => {
      setLoading(true)
      setError('')
      setView('detail')
      setReason('')
      try {
        const result = await getIncomingInvitationDetail(idAjakan)
        if (active) setDetail(result?.data ?? null)
      } catch (requestError) {
        if (active) setError(errorMessage(requestError, 'Detail ajakan gagal dimuat.'))
      } finally {
        if (active) setLoading(false)
      }
    }
    queueMicrotask(run)
    return () => { active = false }
  }, [idAjakan])

  if (!idAjakan) return null

  const decide = async (keputusan) => {
    if (keputusan === 'tolak' && reason.trim().length < 3) {
      setError('Alasan penolakan minimal 3 karakter.')
      return
    }
    setSubmitting(true)
    setError('')
    try {
      const result = await answerInvitation(idAjakan, keputusan, keputusan === 'tolak' ? reason.trim() : undefined)
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: result?.message ?? 'Jawaban tersimpan.', confirmButtonColor: '#2563eb' })
      onDone?.()
    } catch (requestError) {
      setError(errorMessage(requestError, 'Gagal menjawab ajakan.'))
    } finally {
      setSubmitting(false)
    }
  }

  const pengirim = detail?.organisasi_pengirim ?? detail?.pengirim
  const isKolaborator = detail?.jenis === 'kolaborator'
  const jenisBadge = isKolaborator ? 'Kolaborasi' : 'Undangan'
  const jenisFull = isKolaborator ? 'Kolaborator' : 'Undangan'
  const notice = isKolaborator
    ? 'Jika diterima, organisasi Anda menjadi kolaborator pada event ini.'
    : 'Jika diterima, event dapat dilihat anggota organisasi Anda di Jelajah Event.'
  const posters = detail?.event?.posters ?? []
  const posterUtama = posters.find((poster) => poster.is_utama) ?? posters[0] ?? null
  const posterUrl = resolvePoster(posterUtama)
  const kategori = detail?.event?.kategori?.nama_kategori ?? ''
  const jangkauan = detail?.event?.jangkauan_publikasi
    ? (JANGKAUAN_LABEL[detail.event.jangkauan_publikasi] ?? detail.event.jangkauan_publikasi)
    : ''

  return createPortal(
    view === 'reject' && detail ? (
      <RejectInvitationModal
        invitation={detail}
        reason={reason}
        onReasonChange={(value) => { setReason(value); setError('') }}
        onBack={() => backRef.current?.()}
        onSubmit={() => decide('tolak')}
        submitting={submitting}
        error={error}
      />
    ) : (
    <div className="event-modal" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget && !submitting) onClose() }}>
      <div
        className="event-modal__dialog event-modal__dialog--undangan"
        role="dialog"
        aria-modal="true"
        aria-label="Detail Ajakan & Undangan"
      >
        <header className="event-invitation-modal__header">
          {detail && (
            <span className={`ajakan-card__badge is-${isKolaborator ? 'kolaborator' : 'undangan'}`}>{jenisBadge}</span>
          )}
          <button type="button" className="event-invitation-modal__close" onClick={onClose} disabled={submitting}>
            <AdminIcon name="close" size={15} /> Tutup
          </button>
        </header>

        {loading && <p className="event-modal__state">Memuat…</p>}
        {!loading && error && !detail && <p className="event-modal__state is-error">{error}</p>}
        {!loading && detail && (
          <>
            <h2 className="event-invitation-modal__title">{detail.event?.judul ?? '—'}</h2>

            <div className="event-invitation-modal__content">
              <div className="event-invitation-modal__poster">
                {posterUrl
                  ? <img src={posterUrl} alt={detail.event?.judul ?? 'Poster Event'} />
                  : (
                    <div className="event-invitation-modal__poster-fallback">
                      <AdminIcon name="image" size={28} />
                      <span className="event-invitation-modal__poster-label">Poster Event</span>
                      <span className="event-invitation-modal__poster-date">{formatLongDate(detail.event?.tanggal_event)}</span>
                    </div>
                  )}
              </div>

              <div className="event-invitation-modal__info">
                <div className="event-invitation-modal__info-row">
                  <span>Dari</span>
                  <strong>{pengirim?.nama_organisasi ?? '—'}</strong>
                </div>
                <div className="event-invitation-modal__info-row">
                  <span>Jenis</span>
                  <strong>{jenisFull}</strong>
                </div>
                <div className="event-invitation-modal__info-row">
                  <span>Tanggal</span>
                  <strong>{formatEventDate(detail.event?.tanggal_event)}</strong>
                </div>
                {kategori && (
                  <div className="event-invitation-modal__info-row">
                    <span>Kategori</span>
                    <strong>{kategori}</strong>
                  </div>
                )}
                {jangkauan && (
                  <div className="event-invitation-modal__info-row">
                    <span>Jangkauan</span>
                    <strong>{jangkauan}</strong>
                  </div>
                )}
              </div>
            </div>

            {detail.event?.deskripsi && (
              <p className="event-invitation-modal__description">{detail.event.deskripsi}</p>
            )}

            <div className="event-invitation-modal__notice">
              <AdminIcon name="info" size={14} />
              <span>{notice}</span>
            </div>

            {error && detail && <p className="event-modal__state is-error">{error}</p>}
          </>
        )}

        <footer className="event-invitation-modal__footer">
          {detail?.status === 'menunggu' ? (
            <>
              <button type="button" className="event-secondary-button" onClick={() => { setError(''); setReason(''); setView('reject') }} disabled={submitting}>Tolak</button>
              <button type="button" className="event-primary-button" onClick={() => decide('terima')} disabled={submitting}>
                {submitting ? 'Memproses…' : 'Terima'}
              </button>
            </>
          ) : (
            <button type="button" className="event-secondary-button" onClick={onClose} disabled={submitting}>Tutup</button>
          )}
        </footer>
      </div>
    </div>
    ),
    document.body,
  )
}

function formatEventDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
}

function AjakanUndanganPanel() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [detailId, setDetailId] = useState(null)

  const load = async () => {
    setLoading(true)
    setError('')
    try {
      const result = await getIncomingInvitations({ status: 'menunggu' })
      setItems(Array.isArray(result?.data) ? result.data : [])
    } catch (requestError) {
      setError(errorMessage(requestError, 'Ajakan/undangan gagal dimuat.'))
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    queueMicrotask(() => { load() })
  }, [])

  return (
    <aside className="event-management-side card" aria-label="Ajakan dan undangan">
      <header className="ajakan-head">
        <div className="ajakan-head__title">
          <span className="ajakan-head__icon"><AdminIcon name="mail" size={18} /></span>
          <div className="ajakan-head__text">
            <div className="ajakan-head__row">
              <h2>Ajakan &amp; Undangan</h2>
              <span className="ajakan-head__count">{loading ? '…' : items.length}</span>
            </div>
            {items.length > 0 && (
              <p>Tawaran kolaborasi lintas kampus Muhammadiyah Jawa Timur yang memerlukan konfirmasi Anda.</p>
            )}
          </div>
        </div>
        <button
          type="button"
          className="ajakan-refresh"
          onClick={load}
          disabled={loading}
          aria-label="Muat ulang ajakan dan undangan"
          title="Muat ulang"
        >
          <AdminIcon name="rotate-cw" size={16} />
        </button>
      </header>

      {loading && <p className="ajakan-state">Memuat…</p>}
      {!loading && error && <p className="ajakan-state is-error" role="alert">{error}</p>}

      {!loading && !error && items.length === 0 && (
        <div className="ajakan-empty">
          <span className="ajakan-empty__icon"><AdminIcon name="mail" size={22} /></span>
          <strong>Tidak ada ajakan/undangan terbaru</strong>
          <p>Saat organisasi lain mengajak Anda berkolaborasi, undangannya akan muncul di sini.</p>
        </div>
      )}

      {!loading && !error && items.length > 0 && (
        <ul className="ajakan-cards">
          {items.map((item) => (
            <li className="ajakan-card" key={item.id}>
              <span className={`ajakan-card__badge is-${item.jenis}`}>
                {item.jenis === 'kolaborator' ? 'Kolaborasi' : 'Undangan'}
              </span>
              <strong className="ajakan-card__title">{item.event?.judul ?? '—'}</strong>
              <div className="ajakan-card__org-row">
                <span className="ajakan-card__org-logo">
                  <AdminIcon
                    name={String(item.organisasi_pengirim?.jenis_organisasi ?? '').toLowerCase() === 'bem' ? 'landmark' : 'user-group'}
                    size={16}
                  />
                </span>
                <span className="ajakan-card__org-info">
                  <span className="ajakan-card__org-name">{item.organisasi_pengirim?.nama_organisasi ?? '—'}</span>
                  <span className="ajakan-card__dot" aria-hidden="true">•</span>
                  <span className="ajakan-card__date">{formatEventDate(item.event?.tanggal_event)}</span>
                </span>
              </div>
              <button type="button" className="ajakan-card__btn" onClick={() => setDetailId(item.id)}>
                <span>Lihat Detail &amp; Respon</span>
                <AdminIcon name="chevron-right" size={16} />
              </button>
            </li>
          ))}
        </ul>
      )}

      <AjakanDetailModal
        idAjakan={detailId}
        onClose={() => setDetailId(null)}
        onDone={async () => { setDetailId(null); await load() }}
      />
    </aside>
  )
}

export default AjakanUndanganPanel
