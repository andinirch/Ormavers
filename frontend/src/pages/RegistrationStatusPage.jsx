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
  const [submitting, setSubmitting] = useState(false)
  const [submitResult, setSubmitResult] = useState(null)

  useEffect(() => {
    if (isPreview) return undefined
    let active = true
    getMyRegistrationStatus().then((result) => {
      if (!active) return
      setData(result.data)
      setUnavailable(result.unavailable)
      setLoading(false)
    })
    return () => { active = false }
  }, [isPreview])

  const handleResubmit = useCallback(async (file) => {
    setSubmitting(true)
    setSubmitResult(null)
    try {
      const result = isPreview
        ? await simulateResubmit()
        : await resubmitSk(file)

      setSubmitResult(result)

      if (result.ok && data) {
        setData({ ...data, status: 'menunggu', alasanPenolakan: null })
      }
    } catch {
      setSubmitResult({ ok: false, unavailable: false, error: 'Terjadi kesalahan. Silakan coba lagi.' })
    } finally {
      setSubmitting(false)
    }
  }, [isPreview, data])

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

        {!loading && unavailable && !data && (
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
