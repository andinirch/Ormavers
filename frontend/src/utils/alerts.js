import Swal from 'sweetalert2'

/**
 * Alerts Utility - Standard error/success/loading messages dengan SweetAlert2
 * 
 * Features:
 * - Consistent styling di semua halaman
 * - Support retry callback untuk error messages
 * - Support auto-close untuk success messages
 * - Better UX dengan icons dan colors yang jelas
 */

const THEME_COLOR = '#2563eb'
const ERROR_COLOR = '#dc2626'
const SUCCESS_COLOR = '#059669'
const WARNING_COLOR = '#d97706'

/**
 * Show error alert dengan optional retry callback
 * @param {string} message - Error message
 * @param {Function} retryCallback - Optional callback untuk retry button
 * @returns {Promise}
 */
export async function showError(message, retryCallback = null) {
  const config = {
    icon: 'error',
    title: 'Gagal',
    text: message || 'Terjadi kesalahan. Silakan coba lagi.',
    confirmButtonColor: THEME_COLOR,
    confirmButtonText: 'OK'
  }

  // Jika ada retry callback, tambahkan "Coba Lagi" button
  if (retryCallback && typeof retryCallback === 'function') {
    config.showCancelButton = true
    config.confirmButtonText = 'Coba Lagi'
    config.cancelButtonText = 'Tutup'
    config.confirmButtonColor = THEME_COLOR
    config.cancelButtonColor = '#6b7280'
  }

  const result = await Swal.fire(config)

  // Jika user klik "Coba Lagi", jalankan callback
  if (result.isConfirmed && retryCallback) {
    retryCallback()
  }

  return result
}

/**
 * Show success alert
 * @param {string} message - Success message
 * @param {boolean} autoClose - Auto close setelah 2 detik (default: true)
 * @returns {Promise}
 */
export async function showSuccess(message, autoClose = true) {
  return Swal.fire({
    icon: 'success',
    title: 'Berhasil',
    text: message || 'Operasi berhasil dilakukan.',
    confirmButtonColor: SUCCESS_COLOR,
    confirmButtonText: 'OK',
    timer: autoClose ? 2000 : undefined,
    timerProgressBar: autoClose
  })
}

/**
 * Show warning alert
 * @param {string} message - Warning message
 * @returns {Promise}
 */
export async function showWarning(message) {
  return Swal.fire({
    icon: 'warning',
    title: 'Perhatian',
    text: message || 'Harap periksa kembali data Anda.',
    confirmButtonColor: WARNING_COLOR,
    confirmButtonText: 'Mengerti'
  })
}

/**
 * Show loading alert (tidak bisa ditutup user)
 * @param {string} message - Loading message
 */
export function showLoading(message = 'Memuat data...') {
  Swal.fire({
    title: message,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading()
    }
  })
}

/**
 * Close any open Swal
 */
export function closeAlert() {
  Swal.close()
}

/**
 * Show confirmation dialog
 * @param {string} title - Dialog title
 * @param {string} text - Dialog text
 * @param {string} confirmText - Confirm button text
 * @param {string} cancelText - Cancel button text
 * @returns {Promise<boolean>} - true jika confirmed, false jika cancel
 */
export async function showConfirm(
  title = 'Konfirmasi',
  text = 'Apakah Anda yakin?',
  confirmText = 'Ya',
  cancelText = 'Batal'
) {
  const result = await Swal.fire({
    icon: 'question',
    title,
    text,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: cancelText,
    confirmButtonColor: THEME_COLOR,
    cancelButtonColor: '#6b7280'
  })

  return result.isConfirmed
}

/**
 * Show delete confirmation dialog
 * @param {string} itemName - Nama item yang akan dihapus
 * @returns {Promise<boolean>}
 */
export async function showDeleteConfirm(itemName = 'item ini') {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Hapus data?',
    text: `${itemName} yang dihapus tidak dapat dipulihkan.`,
    showCancelButton: true,
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: ERROR_COLOR,
    cancelButtonColor: '#6b7280'
  })

  return result.isConfirmed
}

/**
 * Show network error dengan retry
 * @param {Function} retryCallback - Callback untuk retry
 * @returns {Promise}
 */
export async function showNetworkError(retryCallback) {
  return showError(
    'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
    retryCallback
  )
}

/**
 * Show rate limit error (429)
 * @param {Function} retryCallback - Callback untuk retry
 * @returns {Promise}
 */
export async function showRateLimitError(retryCallback = null) {
  const config = {
    icon: 'warning',
    title: 'Terlalu Banyak Permintaan',
    html: `
      <p>Anda telah melakukan terlalu banyak permintaan.</p>
      <p>Silakan tunggu beberapa saat sebelum mencoba lagi.</p>
    `,
    confirmButtonColor: THEME_COLOR,
    confirmButtonText: retryCallback ? 'Coba Lagi' : 'OK',
    showCancelButton: !!retryCallback,
    cancelButtonText: 'Tutup',
    cancelButtonColor: '#6b7280',
    timer: 5000,
    timerProgressBar: true
  }

  const result = await Swal.fire(config)

  if (result.isConfirmed && retryCallback) {
    retryCallback()
  }

  return result
}

/**
 * Show validation error
 * @param {string} message - Validation error message
 * @returns {Promise}
 */
export async function showValidationError(message) {
  return Swal.fire({
    icon: 'error',
    title: 'Data Tidak Valid',
    text: message || 'Harap periksa kembali data yang Anda masukkan.',
    confirmButtonColor: ERROR_COLOR,
    confirmButtonText: 'OK'
  })
}

/**
 * Toast notification (muncul di pojok, auto-hide)
 * @param {string} message - Toast message
 * @param {string} type - 'success' | 'error' | 'warning' | 'info'
 */
export function showToast(message, type = 'success') {
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer)
      toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
  })

  Toast.fire({
    icon: type,
    title: message
  })
}

// Export all functions
export default {
  showError,
  showSuccess,
  showWarning,
  showLoading,
  closeAlert,
  showConfirm,
  showDeleteConfirm,
  showNetworkError,
  showRateLimitError,
  showValidationError,
  showToast
}
