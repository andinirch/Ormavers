import { useState, useRef } from 'react'
import { validateDocumentFile } from '../../validation/rules.js'

function SkResubmitForm({ onSubmit, submitting, documentName = 'Dokumen SK', documentType }) {
  const [file, setFile] = useState(null)
  const [error, setError] = useState(null)
  const inputRef = useRef(null)

  const handleChange = (e) => {
    const selected = e.target.files?.[0] ?? null
    setFile(selected)
    if (selected) {
      const err = validateDocumentFile(selected, documentName)
      setError(err)
    } else {
      setError(null)
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const err = validateDocumentFile(file, documentName)
    if (err) {
      setError(err)
      return
    }
    const submitted = await onSubmit(file, documentType || (documentName === 'SK Organisasi' ? 'sk_organisasi' : 'sk_kepengurusan'))

    if (submitted) {
      setFile(null)
      setError(null)
      if (inputRef.current) inputRef.current.value = ''
    }
  }

  const handleDropzoneClick = () => {
    inputRef.current?.click()
  }

  const hasError = Boolean(error)

  return (
    <form onSubmit={handleSubmit} aria-busy={submitting}>
      <p className="status-section-label">Unggah Ulang Dokumen</p>
      <div
        className={`upload-dropzone${hasError ? ' upload-dropzone--has-error' : ''}`}
        onClick={handleDropzoneClick}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault()
            handleDropzoneClick()
          }
        }}
      >
        <input
          ref={inputRef}
          type="file"
          accept="application/pdf"
           onChange={handleChange}
           onClick={(event) => event.stopPropagation()}
           className="upload-dropzone__input"

          tabIndex={-1}
          aria-label={`Pilih ${documentName} baru`}
          aria-describedby={hasError ? 'document-upload-hint document-upload-error' : 'document-upload-hint'}
          aria-invalid={hasError}

        />
        <span className="upload-dropzone__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" className="upload-dropzone__svg">
            <path d="M7 3.5h7l4 4V20.5H7z" />
            <path d="M14 3.5v4h4M12 16V10m0 0-2.5 2.5M12 10l2.5 2.5" />
          </svg>
        </span>
        {file ? (
          <span className="upload-dropzone__filename">{file.name}</span>
        ) : (
          <span className="upload-dropzone__label">Pilih dokumen SK baru</span>
        )}
        <span id="document-upload-hint" className="upload-dropzone__hint">PDF, maksimal 2 MB</span>
      </div>
      {hasError && <p id="document-upload-error" className="upload-error" role="alert">{error}</p>}
      <button
        type="submit"
        className="status-btn-primary"
        disabled={!file || hasError || submitting}
      >
        {submitting && <span className="status-btn-primary__spinner" aria-hidden="true" />}
        {submitting ? 'Mengirim\u2026' : (
          <>
            <svg viewBox="0 0 24 24" className="status-btn-primary__send-icon" aria-hidden="true">
              <path d="m4 4 16 8-16 8 3.5-8z" />
              <path d="M7.5 12H20" />
            </svg>
            Kirim ulang
          </>
        )}
      </button>
    </form>
  )
}

export default SkResubmitForm
