export const PDF_MIME = 'application/pdf'
export const MAX_DOCUMENT_BYTES = 2 * 1024 * 1024
export const MAX_DOCUMENT_LABEL = '2 MB'
export const MAX_PHOTO_BYTES = 1 * 1024 * 1024
export const MAX_PHOTO_LABEL = '1 MB'

export function isNonEmpty(value) {
  if (typeof value === 'string') {
    return value.trim().length > 0
  }
  return Boolean(value)
}

export function requiredError(label) {
  return `${label} wajib diisi.`
}

export function isPdfFile(file) {
  if (!file) return false
  const byMime = file.type === PDF_MIME
  const byExtension =
    typeof file.name === 'string' && file.name.toLowerCase().endsWith('.pdf')
  return byMime || byExtension
}

export function validateDocumentFile(file, label = 'Dokumen') {
  if (!file) {
    return `${label} wajib diunggah.`
  }
  if (!isPdfFile(file)) {
    return `${label} harus berformat PDF.`
  }
  if (file.size > MAX_DOCUMENT_BYTES) {
    return `${label} maksimal ${MAX_DOCUMENT_LABEL}.`
  }
  return null
}

export function isJpegFile(file) {
  if (!file) return false
  if (file.type === 'image/jpeg') return true
  const name = typeof file.name === 'string' ? file.name.toLowerCase() : ''
  return name.endsWith('.jpg') || name.endsWith('.jpeg')
}

export function validatePhotoFile(file, label = 'Foto Selfie dengan KTM') {
  if (!file) {
    return `${label} wajib diunggah.`
  }
  if (!isJpegFile(file)) {
    return `${label} harus berformat JPG/JPEG.`
  }
  if (file.size > MAX_PHOTO_BYTES) {
    return `${label} maksimal ${MAX_PHOTO_LABEL}.`
  }
  return null
}

export function validateRequiredFields(fields) {
  const errors = {}
  for (const field of fields) {
    if (!isNonEmpty(field.value)) {
      errors[field.name] = requiredError(field.label)
    }
  }
  return errors
}
