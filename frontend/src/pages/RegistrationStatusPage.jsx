import { useEffect, useState, useCallback, useMemo } from 'react'
import StatusCard from '../components/status/StatusCard.jsx'
import PreviewBanner from '../components/status/PreviewBanner.jsx'
import { getMyRegistrationStatus, resubmitSk } from '../services/registrationStatusApi.js'
import { getPreviewKey, getFixture, simulateResubmit } from '../services/registrationStatusFixture.js'

function RegistrationStatusPage() {
  const previewKey = getPreviewKey()
  const isPreview = Boolean(previewKey)

  const previewData = useMemo(
    () => (isPreview ? getFixture(previewKey) : null),
    [isPreview, previewKey],
  )

  const [data, setData] = useState(() => previewData)
  const [loading, setLoading] = useState(() => !isPreview)
  const [unavailable, setUnavailable] = useState(() => (isPreview ? !previewData : false))
  const [loadError, setLoadError] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [submitResult, setSubmitResult] = useState(null)
  const displayedData = isPreview ? (data ?? previewData) : data

  const loadStatus = useCallback(async () => {
    setLoading(true)
    setLoadError(null)
    try {
      const result = await getMyRegistrationStatus()
      setData(result.data)
      setUnavailable(result.unavailable)
      if (result.error) setLoadError(result.error)
    } catch {
      setData(null)
      setUnavailable(false)
      setLoadError('Status registrasi gagal dimuat. Silakan coba lagi.')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    if (isPreview) return undefined
    Promise.resolve().then(() => loadStatus()).catch(() => {})
    return undefined
  }, [isPreview, loadStatus])

  const handleResubmit = useCallback(async (file, documentType) => {
    setSubmitting(true)
    setSubmitResult(null)
    try {
      const result = isPreview
        ? await simulateResubmit(previewKey === 'ditolak-gagal')
        : await resubmitSk(file, documentType)

      setSubmitResult(result)

      if (result.ok && displayedData) {
        setData({ ...displayedData, status: 'menunggu', alasanPenolakan: null, namaDokumenDitolak: null })
      }
      return result.ok
    } catch {
      setSubmitResult({ ok: false, unavailable: false, error: 'Terjadi kesalahan. Silakan coba lagi.' })
      return false
    } finally {
      setSubmitting(false)
    }
  }, [isPreview, previewKey, displayedData])

  return (
    <>
      {isPreview && <PreviewBanner />}
      <div className="status-page">
        <span className="status-page__pill">
          <span className="status-page__pill-dot" />
          Verifikasi Registrasi Ormavers
        </span>
        <h1 className="status-page__title">Status registrasi</h1>
        <p className="status-page__subtitle">Pantau hasil verifikasi registrasi kamu</p>

        {loading && (
          <div className="status-loading">
            <span className="status-loading__spinner" />
            Memuat data registrasi...
          </div>
        )}

        {!loading && loadError && (
          <div className="status-message status-message--error" role="alert">
            <p>{loadError}</p>
            <button type="button" className="status-btn-secondary" onClick={loadStatus}>
              Coba lagi
            </button>
          </div>
        )}

        {!loading && !loadError && unavailable && !data && (
          <p className="status-unavailable">
            Data status registrasi belum tersedia. Fitur ini akan aktif setelah integrasi backend tersedia.
          </p>
        )}

        {!loading && data && (
          <StatusCard
            data={data}
            onResubmit={handleResubmit}
            submitting={submitting}
            submitResult={submitResult}
          />
        )}
      </div>
    </>
  )
}

export default RegistrationStatusPage
