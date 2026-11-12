import { useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import AdminIcon from '../admin/AdminIcon.jsx'

function ConfirmDeleteEventModal({ open, deleting, onCancel, onConfirm }) {
  const cancelRef = useRef(null)
  const onCancelRef = useRef(onCancel)
  const deletingRef = useRef(deleting)

  useEffect(() => { onCancelRef.current = onCancel })
  useEffect(() => { deletingRef.current = deleting })

  useEffect(() => {
    if (!open) return undefined
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    cancelRef.current?.focus()
    const onKeyDown = (event) => {
      if (event.key === 'Escape' && !deletingRef.current) onCancelRef.current?.()
    }
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('keydown', onKeyDown)
      document.body.style.overflow = previousOverflow
    }
  }, [open])

  if (!open) return null

  return createPortal(
    <div
      className="delete-event-modal"
      role="presentation"
      onMouseDown={(event) => { if (event.target === event.currentTarget && !deleting) onCancel() }}
    >
      <div
        className="delete-event-modal__card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-event-title"
        aria-describedby="delete-event-desc"
      >
        <header className="delete-event-modal__head">
          <span className="delete-event-modal__icon" aria-hidden="true">
            <AdminIcon name="warning" size={20} />
          </span>
          <h2 id="delete-event-title" className="delete-event-modal__title">Hapus event ini?</h2>
        </header>

        <p id="delete-event-desc" className="delete-event-modal__desc">
          Penghapusan bersifat permanen. Poster serta ajakan dan undangan terkait ikut terhapus,
          dan kolaborator serta organisasi yang telah menerima undangan akan mendapat notifikasi.
        </p>

        <footer className="delete-event-modal__footer">
          <button ref={cancelRef} type="button" className="delete-event-modal__cancel" onClick={onCancel} disabled={deleting}>
            Batal
          </button>
          <button type="button" className="delete-event-modal__confirm" onClick={onConfirm} disabled={deleting}>
            {deleting ? 'Menghapus…' : 'Hapus Event'}
          </button>
        </footer>
      </div>
    </div>,
    document.body,
  )
}

export default ConfirmDeleteEventModal
