import { Link } from 'react-router-dom'
import StatusPill from './StatusPill.jsx'
import RejectionReason from './RejectionReason.jsx'
import SkResubmitForm from './SkResubmitForm.jsx'

function formatDate(isoDate) {
  try {
    const date = new Date(isoDate + 'T00:00:00')
    return 'Dikirim ' + new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    }).format(date)
  } catch {
    return ''
  }
}

function StatusCard({ data, onResubmit, submitting, submitResult }) {
  const { status, namaPendaftar, tanggalKirim, alasanPenolakan, jenisPendaftar } = data
  const isOrganisasi = jenisPendaftar === 'organisasi'
  const isDitolak = status === 'ditolak'

  return (
    <div className="status-card">
      <div className="status-card__header">
        <div style={{ minWidth: 0, flex: 1 }}>
          <h2 className="status-card__name">{namaPendaftar}</h2>
          <p className="status-card__date">{formatDate(tanggalKirim)}</p>
        </div>
        <StatusPill status={status} />
      </div>
      <hr className="status-card__divider" />

      {status === 'menunggu' && (
        <>
          <p className="status-body__text">
            Registrasi kamu sedang diperiksa oleh verifikator. Cek lagi nanti.
          </p>
          <div className="status-info-box status-info-box--amber">
            <span className="status-info-box__icon" aria-hidden="true">{'\u231B'}</span>
            <div className="status-info-box__content">
              <strong>Estimasi Peninjauan</strong>
              Proses verifikasi berkas umumnya memakan waktu 1&ndash;2 hari kerja.
            </div>
          </div>
        </>
      )}

      {status === 'disetujui' && (
        <>
          <p className="status-body__text">
            Akun kamu sudah aktif. Masuk dengan username dan password kamu.
          </p>
          <Link to="/login" className="status-btn-primary">
            Masuk &rarr;
          </Link>
        </>
      )}

      {isDitolak && (
        <>
          <RejectionReason reason={alasanPenolakan} />

          {isOrganisasi && (
            <>
              {submitResult?.ok && (
                <div className="status-info-box status-info-box--amber" style={{ marginBottom: '1rem' }}>
                  <span className="status-info-box__icon" aria-hidden="true">{'\u2705'}</span>
                  <div className="status-info-box__content">
                    <strong>Dokumen terkirim</strong>
                    Registrasi kamu kembali berstatus Menunggu untuk diverifikasi ulang.
                  </div>
                </div>
              )}
              {submitResult?.error && (
                <p className="upload-error" role="alert">{submitResult.error}</p>
              )}
              {submitResult?.unavailable && (
                <p className="status-unavailable" style={{ padding: '0.5rem 0', textAlign: 'left' }}>
                  Pengiriman dokumen belum dapat diproses. Integrasi backend belum tersedia.
                </p>
              )}
              {!submitResult?.ok && (
                <SkResubmitForm onSubmit={onResubmit} submitting={submitting} />
              )}
            </>
          )}
        </>
      )}
    </div>
  )
}

export default StatusCard
