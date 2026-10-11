import { useState, useRef } from 'react'
import { validateDocumentFile } from '../../validation/rules.js'

function SkResubmitForm({ onSubmit, submitting }) {
  const [file, setFile] = useState(null)
  const [error, setError] = useState(null)
  const inputRef = useRef(null)

  const handleChange = (e) => {
    const selected = e.target.files?.[0] ?? null
    setFile(selected)
    if (selected) {
      const err = validateDocumentFile(selected, 'Dokumen SK')
      setError(err)
    } else {
      setError(null)
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    const err = validateDocumentFile(file, 'Dokumen SK')
    if (err) {
      setError(err)
      return
    }
    onSubmit(file)
  }

  const handleDropzoneClick = () => {
    inputRef.current?.click()
  }

  const hasError = Boolean(error)

  return (
    <form onSubmit={handleSubmit}>
      <p className="status-section-label">Unggah Ulang Dokumen</p>
      <div
        className={`upload-dropzone${hasError ? ' upload-dropzone--has-error' : ''}`}
        onClick={handleDropzoneClick}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') handleDropzoneClick() }}
      >
        <input
          ref={inputRef}
          type="file"
          accept="application/pdf"
          onChange={handleChange}
          className="upload-dropzone__input"
          tabIndex={-1}
          aria-label="Pilih dokumen SK baru"
        />
        <span className="upload-dropzone__icon" aria-hidden="true">{'\uD83D\uDCC4'}</span>
        {file ? (
          <span className="upload-dropzone__filename">{file.name}</span>
        ) : (
          <span className="upload-dropzone__label">Pilih dokumen SK baru</span>
        )}
        <span className="upload-dropzone__hint">PDF, maksimal 5 MB</span>
      </div>
      {hasError && <p className="upload-error" role="alert">{error}</p>}
      <button
        type="submit"
        className="status-btn-primary"
        disabled={!file || hasError || submitting}
      >
        {submitting && <span className="status-btn-primary__spinner" aria-hidden="true" />}
        {submitting ? 'Mengirim\u2026' : '\u27A4 Kirim ulang'}
      </button>
    </form>
  )
}

export default SkResubmitForm
