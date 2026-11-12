import { useEffect, useRef } from 'react'
import AdminIcon from '../admin/AdminIcon.jsx'

const FOCUSABLE = 'button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [href], [tabindex]:not([tabindex="-1"])'

function formatEventDate(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
}

function RejectInvitationModal({ invitation, reason, onReasonChange, onBack, onSubmit, submitting, error }) {
  const textareaRef = useRef(null)
  const cardRef = useRef(null)

  useEffect(() => {
    textareaRef.current?.focus()
    const onKeyDown = (event) => {
      if (event.key !== 'Tab' || !cardRef.current) return
      const nodes = [...cardRef.current.querySelectorAll(FOCUSABLE)]
      if (!nodes.length) return
      const first = nodes[0]
      const last = nodes[nodes.length - 1]
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
    }
    document.addEventListener('keydown', onKeyDown)
    return () => document.removeEventListener('keydown', onKeyDown)
  }, [])

  const isKolaborator = invitation?.jenis === 'kolaborator'
  const badge = isKolaborator ? 'Kolaborasi' : 'Undangan'
  const pengirim = invitation?.organisasi_pengirim ?? invitation?.pengirim
  const meta = [
    pengirim?.nama_organisasi ? `Dari ${pengirim.nama_organisasi}` : null,
    formatEventDate(invitation?.event?.tanggal_event),
  ].filter(Boolean).join(' · ')

  return (
    <div
      className="reject-invitation-modal"
      role="presentation"
      onMouseDown={(event) => { if (event.target === event.currentTarget && !submitting) onBack() }}
    >
      <div
        ref={cardRef}
        className="reject-invitation-modal__card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reject-invitation-title"
        aria-describedby="reject-invitation-desc"
      >
        <header className="reject-invitation-modal__header">
          <span className={`ajakan-card__badge is-${isKolaborator ? 'kolaborator' : 'undangan'}`}>{badge}</span>
          <button type="button" className="reject-invitation-modal__close" onClick={onBack} disabled={submitting}>
            <AdminIcon name="close" size={15} /> Batal
          </button>
        </header>

        <h2 id="reject-invitation-title" className="reject-invitation-modal__title">{invitation?.event?.judul ?? '—'}</h2>
        {meta && <p className="reject-invitation-modal__meta">{meta}</p>}

        <div className="reject-invitation-modal__field">
          <label htmlFor="reject-reason">Alasan penolakan (wajib)</label>
          <textarea
            ref={textareaRef}
            id="reject-reason"
            className="reject-invitation-modal__textarea"
            rows={5}
            value={reason}
            onChange={(event) => onReasonChange(event.target.value)}
            placeholder="Tulis alasan penolakan..."
            disabled={submitting}
            aria-describedby="reject-invitation-desc"
          />
          <p id="reject-invitation-desc" className="reject-invitation-modal__helper">
            <AdminIcon name="info" size={13} />
            <span>Alasan akan dikirim ke organisasi pengirim.</span>
          </p>
        </div>

        {error && <p className="reject-invitation-modal__error" role="alert">{error}</p>}

        <footer className="reject-invitation-modal__footer">
          <button type="button" className="reject-invitation-modal__back" onClick={onBack} disabled={submitting}>
            Kembali
          </button>
          <button type="button" className="reject-invitation-modal__submit" onClick={onSubmit} disabled={submitting}>
            {submitting ? 'Mengirim…' : 'Kirim Penolakan'}
          </button>
        </footer>
      </div>
    </div>
  )
}

export default RejectInvitationModal
