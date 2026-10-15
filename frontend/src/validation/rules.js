export const PDF_MIME = 'application/pdf'
export const MAX_DOCUMENT_BYTES = 5 * 1024 * 1024
export const MAX_DOCUMENT_LABEL = '5 MB'

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

export function isJpgOrPngFile(file) {
  if (!file) return false
  const allowedMimes = ['image/jpeg', 'image/png']
  if (allowedMimes.includes(file.type)) return true
  const name = typeof file.name === 'string' ? file.name.toLowerCase() : ''
  return name.endsWith('.jpg') || name.endsWith('.jpeg') || name.endsWith('.png')
}

export function validatePhotoFile(file, label = 'Foto Selfie dengan KTM') {
  if (!file) {
    return `${label} wajib diunggah.`
  }
  if (!isJpgOrPngFile(file)) {
    return `${label} harus berformat JPG/JPEG atau PNG.`
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
