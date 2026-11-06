import { useState, useRef } from 'react'
import { validateDocumentFile, validatePhotoFile } from '../../validation/rules.js'

function validateFor(doc, file) {
  return doc.kind === 'foto'
    ? validatePhotoFile(file, doc.label)
    : validateDocumentFile(file, doc.label)
}

function UploadBox({ doc, file, error, disabled, onChange }) {
  const inputRef = useRef(null)
  const hasError = Boolean(error)
  const inputId = `resubmit-${doc.jenis}`
  const hintId = `${inputId}-hint`
  const errorId = `${inputId}-error`

  return (
    <div className="resubmit-box">
      <label htmlFor={inputId} className="status-section-label">Unggah ulang {doc.label}</label>
      <div
        className={`upload-dropzone${hasError ? ' upload-dropzone--has-error' : ''}`}
        onClick={() => inputRef.current?.click()}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault()
            inputRef.current?.click()
          }
        }}
      >
        <input
          id={inputId}
          ref={inputRef}
          type="file"
          accept={doc.accept}
          className="upload-dropzone__input"
          tabIndex={-1}
          disabled={disabled}
          onClick={(event) => event.stopPropagation()}
          onChange={(e) => onChange(doc.jenis, e.target.files?.[0] ?? null)}
          aria-describedby={hasError ? `${hintId} ${errorId}` : hintId}
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
          <span className="upload-dropzone__label">Pilih {doc.label} baru</span>
        )}
        <span id={hintId} className="upload-dropzone__hint">
          {doc.kind === 'foto' ? `JPG/JPEG, maksimal ${doc.ukuranLabel}` : `PDF, maksimal ${doc.ukuranLabel}`}
        </span>
      </div>
      {hasError && <p id={errorId} className="upload-error" role="alert">{error}</p>}
    </div>
  )
}

/**
 * Satu kotak unggah per dokumen yang ditolak; satu tombol kirim untuk semua kotak.
 * @param {{documents: Array<{jenis:string,label:string,accept:string,ukuranLabel:string,kind:string}>, submitting: boolean, onSubmit: (filesMap: Object<string, File>) => Promise<boolean>|boolean}} props
 */
function ResubmitDocumentsForm({ documents, submitting, onSubmit }) {
  const [files, setFiles] = useState({})
  const [errors, setErrors] = useState({})
  const [formError, setFormError] = useState(null)

  const handleFileChange = (jenis, file) => {
    const doc = documents.find((d) => d.jenis === jenis)
    setFiles((prev) => ({ ...prev, [jenis]: file ?? undefined }))
    setErrors((prev) => ({ ...prev, [jenis]: file && doc ? validateFor(doc, file) : null }))
    setFormError(null)
  }

  const canSubmit = !submitting

  const handleSubmit = async (e) => {
    e.preventDefault()
    // Semua kotak yang tampil wajib terisi dan valid (file kosong → "wajib diunggah").
    const nextErrors = {}
    const filesMap = {}
    for (const doc of documents) {
      const file = files[doc.jenis]
      const err = validateFor(doc, file)
      if (err) nextErrors[doc.jenis] = err
      else filesMap[doc.jenis] = file
    }
    if (Object.keys(nextErrors).length > 0) {
      setErrors((prev) => ({ ...prev, ...nextErrors }))
      setFormError('Lengkapi semua dokumen yang diminta sebelum mengirim.')
      return
    }

    const submitted = await onSubmit(filesMap)
    if (submitted) {
      setFiles({})
      setErrors({})
    }
  }

  return (
    <form onSubmit={handleSubmit} aria-busy={submitting} className="resubmit-form">
      <p className="status-section-label">Unggah Ulang Dokumen</p>
      <div className="resubmit-boxes">
        {documents.map((doc) => (
          <UploadBox
            key={doc.jenis}
            doc={doc}
            file={files[doc.jenis]}
            error={errors[doc.jenis]}
            disabled={submitting}
            onChange={handleFileChange}
          />
        ))}
      </div>
      {formError && <p className="upload-error" role="alert">{formError}</p>}
      <button type="submit" className="status-btn-primary" disabled={!canSubmit}>
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

export default ResubmitDocumentsForm
