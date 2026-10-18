export function StatusBadge({ status }) {
  const normalized = (status ?? 'menunggu').toLowerCase()
  const label = normalized === 'disetujui'
    ? 'Disetujui'
    : normalized === 'ditolak'
      ? 'Ditolak'
      : 'Menunggu'
  return (
    <span className={`verification-badge verification-badge--${normalized}`}>
      <i />
      <span>{label}</span>
    </span>
  )
}

export function DocumentModal({ title, url, fileName, type = 'pdf', onClose }) {
  return (
    <div className="verification-modal-backdrop" role="dialog" aria-modal="true" onClick={onClose}>
      <div className="verification-modal" onClick={(e) => e.stopPropagation()}>
        <button type="button" className="verification-modal__close" onClick={onClose} aria-label="Tutup preview">
          ×
        </button>
        <h2>{title}</h2>
        <div className="verification-document-preview">
          {type === 'image' ? (
            url ? (
              <img src={url} alt={title} style={{ maxWidth: '100%', maxHeight: '300px', objectFit: 'contain', borderRadius: '8px' }} />
            ) : (
              <div className="verification-photo-placeholder">Foto KTM Mahasiswa</div>
            )
          ) : (
            <>
              <strong>PDF</strong>
              <span>{fileName || 'Dokumen Surat Keputusan'}</span>
              <small>Pratinjau berkas digital</small>
            </>
          )}
        </div>
        {url && (
          <div style={{ marginTop: '16px', textAlign: 'center' }}>
            <a href={url} target="_blank" rel="noopener noreferrer" className="verification-button verification-button--approve" style={{ display: 'inline-block', padding: '10px 20px', textDecoration: 'none', minHeight: 'auto', fontSize: '14px' }}>
              Buka di Tab Baru
            </a>
          </div>
        )}
      </div>
    </div>
  )
}

export function ConfirmationModal({ title, message, confirmLabel, confirmVariant = 'approve', onConfirm, onCancel }) {
  return (
    <div className="verification-modal-backdrop" role="dialog" aria-modal="true" onClick={onCancel}>
      <div className="verification-modal" onClick={(e) => e.stopPropagation()}>
        <button type="button" className="verification-modal__close" onClick={onCancel} aria-label="Batal">
          ×
        </button>
        <h2>{title}</h2>
        <p style={{ color: 'var(--v-muted)', fontSize: '15px', lineHeight: 1.5, margin: '0 0 24px' }}>
          {message}
        </p>
        <div className="verification-actions" style={{ marginTop: 0 }}>
          <button type="button" className="verification-button verification-button--reject" onClick={onCancel}>
            Batal
          </button>
          <button
            type="button"
            className={`verification-button verification-button--${confirmVariant}`}
            onClick={onConfirm}
          >
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}

export function Toast({ message, onClose }) {
  if (!message) return null
  return (
    <div className="verification-toast" role="status">
      <button type="button" onClick={onClose} aria-label="Tutup notifikasi">×</button>
      <span>{message}</span>
    </div>
  )
}
