import { useEffect, useState, useCallback } from 'react'
import StatusCard from '../components/status/StatusCard.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import { getMyRegistrationStatus, resubmitSk } from '../services/registrationStatusApi.js'

function RegistrationStatusPage() {
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [unavailable, setUnavailable] = useState(false)
  const [loadError, setLoadError] = useState(null)
  const [submitting, setSubmitting] = useState(false)
  const [submitResult, setSubmitResult] = useState(null)
  const displayedData = data

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
    Promise.resolve().then(() => loadStatus()).catch(() => {})
  }, [loadStatus])

  const handleApprovedLogin = useCallback(() => {
    if (user?.username) {
      localStorage.setItem(`ormavers:status-seen:${user.username}`, 'true')
    }
  }, [user])

  const handleResubmit = useCallback(async (file, documentType) => {
    setSubmitting(true)
    setSubmitResult(null)
    try {
      const result = await resubmitSk(file, documentType)

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
  }, [displayedData])

  return (
    <>
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
            onApprovedLogin={handleApprovedLogin}
            submitting={submitting}
            submitResult={submitResult}
          />
        )}
      </div>
    </>
  )
}

export default RegistrationStatusPage
