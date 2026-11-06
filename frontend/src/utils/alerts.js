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
export default {
  showError,
  showRateLimitError
}
