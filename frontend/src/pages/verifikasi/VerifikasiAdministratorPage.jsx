import { useEffect, useState } from 'react'
import {
  approveBemRegistration,
  getBemDocument,
  listBemRegistrations,
  rejectBemRegistration,
  showBemRegistration,
} from '../../api/verifikasi.js'
import {
  ConfirmationModal,
  DocumentModal,
  StatusBadge,
  Toast,
} from '../../components/verification/VerificationPanel.jsx'

function FileTextIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
      <polyline points="14 2 14 8 20 8" />
    </svg>
  )
}

function DownloadIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
      <polyline points="7 10 12 15 17 10" />
      <line x1="12" y1="15" x2="12" y2="3" />
    </svg>
  )
}

function EyeIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
      <circle cx="12" cy="12" r="3" />
    </svg>
  )
}

function CheckIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <polyline points="20 6 9 17 4 12" />
    </svg>
  )
}

function XIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <line x1="18" y1="6" x2="6" y2="18" />
      <line x1="6" y1="6" x2="18" y2="18" />
    </svg>
  )
}

export default function VerifikasiAdministratorPage() {
  const [items, setItems] = useState([])
  const [selectedId, setSelectedId] = useState(null)
  const [selectedDetail, setSelectedDetail] = useState(null)
  const [loading, setLoading] = useState(true)
  const [detailLoading, setDetailLoading] = useState(false)
  const [actionLoading, setActionLoading] = useState(false)
  const [error, setError] = useState('')
  const [rejectionReason, setRejectionReason] = useState('')
  const [rejectionError, setRejectionError] = useState('')
  const [toastMessage, setToastMessage] = useState('')
  const [confirmDialog, setConfirmDialog] = useState(null)
  const [previewDoc, setPreviewDoc] = useState(null)
  const [downloadingDoc, setDownloadingDoc] = useState(null)

  const loadList = async () => {
    setLoading(true)
    setError('')
    try {
      const res = await listBemRegistrations()
      const data = res?.data?.pendaftaran ?? []
      setItems(data)
      if (data.length > 0) {
        setSelectedId((current) => (current && data.some((it) => it.id_pendaftaran_organisasi === current) ? current : data[0].id_pendaftaran_organisasi))
      } else {
        setSelectedId(null)
        setSelectedDetail(null)
      }
    } catch (err) {
      setError(err?.response?.data?.message ?? 'Gagal memuat daftar registrasi BEM dari server.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    queueMicrotask(loadList)
  }, [])

  useEffect(() => {
    if (!selectedId) {
      queueMicrotask(() => setSelectedDetail(null))
      return
    }
    let cancelled = false
    const fetchDetail = async () => {
      setDetailLoading(true)
      try {
        const res = await showBemRegistration(selectedId)
        if (!cancelled) {
          setSelectedDetail(res?.data?.pendaftaran ?? null)
          setRejectionReason('')
          setRejectionError('')
        }
      } catch (err) {
        if (!cancelled) {
          setError(err?.response?.data?.message ?? 'Gagal memuat detail pendaftaran.')
        }
      } finally {
        if (!cancelled) setDetailLoading(false)
      }
    }
    queueMicrotask(fetchDetail)
    return () => {
      cancelled = true
    }
  }, [selectedId])

  const handleOpenDoc = async (jenis, label, type = 'pdf') => {
    if (!selectedId) return
    try {
      const res = await getBemDocument(selectedId, jenis)
      const blobUrl = URL.createObjectURL(res.data)
      setPreviewDoc({ title: label, url: blobUrl, type })
    } catch (err) {
      setToastMessage(err?.response?.data?.message ?? 'Gagal membuka dokumen.')
    }
  }

  const handleDownloadDoc = async (jenis, fileName, event) => {
    event.stopPropagation()
    if (!selectedId || downloadingDoc) return
    setDownloadingDoc(jenis)
    try {
      const response = await getBemDocument(selectedId, jenis)
      const blobUrl = URL.createObjectURL(response.data)
      const anchor = document.createElement('a')
      anchor.href = blobUrl
      anchor.download = fileName
      document.body.appendChild(anchor)
      anchor.click()
      anchor.remove()
      URL.revokeObjectURL(blobUrl)
    } catch (err) {
      setToastMessage(err?.response?.data?.message ?? 'Gagal mengunduh dokumen.')
    } finally {
      setDownloadingDoc(null)
    }
  }

  const handleApprove = () => {
    setConfirmDialog({
      type: 'approve',
      title: 'Setujui Registrasi BEM',
      message: `Apakah Anda yakin ingin menyetujui pendaftaran ${selectedDetail?.nama_organisasi}? Akun organisasi akan diaktifkan secara resmi.`,
      confirmLabel: 'Setujui',
      confirmVariant: 'approve',
    })
  }

  const handleReject = () => {
    if (!rejectionReason.trim()) {
      setRejectionError('Alasan penolakan wajib diisi.')
      return
    }
    setRejectionError('')
    setConfirmDialog({
      type: 'reject',
      title: 'Tolak Registrasi BEM',
      message: `Apakah Anda yakin ingin menolak pendaftaran ${selectedDetail?.nama_organisasi}? Alasan penolakan akan dikirimkan kepada pendaftar.`,
      confirmLabel: 'Tolak',
      confirmVariant: 'reject',
    })
  }

  const executeAction = async () => {
    if (!confirmDialog || !selectedId) return
    setActionLoading(true)
    try {
      if (confirmDialog.type === 'approve') {
        await approveBemRegistration(selectedId)
        setToastMessage('Pendaftaran BEM berhasil disetujui.')
      } else {
        await rejectBemRegistration(selectedId, rejectionReason.trim())
        setToastMessage('Pendaftaran BEM telah ditolak.')
      }
      setConfirmDialog(null)
      setRejectionReason('')
      await loadList()
    } catch (err) {
      setToastMessage(err?.response?.data?.message ?? 'Aksi verifikasi gagal diproses.')
    } finally {
      setActionLoading(false)
    }
  }

  const formatDate = (isoString) => {
    if (!isoString) return '—'
    try {
      return new Date(isoString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      })
    } catch {
      return isoString
    }
  }

  return (
    <main className="admin-page verification-admin-page verification-administrator-page">
      <div className="verification-page">
      <div className="verification-page__heading">
        <div className="verification-eyebrow">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <polyline points="9 12 11 14 15 10" />
          </svg>
          PANEL KURASI & VALIDASI
        </div>
        <h1>Daftar registrasi BEM</h1>
        <p>Verifikasi pendaftaran BEM dari seluruh kampus</p>
      </div>

      {error && <div className="verification-error" style={{ marginBottom: '18px' }}>{error}</div>}

      <div className="verification-layout">
        <section className="verification-list-card" aria-label="Daftar permohonan registrasi BEM">
          <header className="verification-card-heading">
            <h2>
              <span className="verification-accent" aria-hidden="true" />
              Permohonan Registrasi BEM
            </h2>
            <span className="verification-count">
              {loading ? 'Memuat…' : `${items.length} Pengajuan Masuk`}
            </span>
          </header>

          <div className="verification-list verification-list--bem">
            <div className="verification-list__header">
              <span>Nama BEM</span>
              <span>Kampus</span>
              <span>Tanggal</span>
              <span>Status</span>
            </div>

            {loading ? (
              <div className="verification-empty">Memuat daftar registrasi…</div>
            ) : items.length === 0 ? (
              <div className="verification-empty">
                <strong>Tidak ada permohonan registrasi BEM</strong>
                <p style={{ margin: '6px 0 0', fontSize: '13px' }}>Semua permohonan telah selesai diverifikasi.</p>
              </div>
            ) : (
              items.map((item) => {
                const isSelected = item.id_pendaftaran_organisasi === selectedId
                return (
                  <button
                    type="button"
                    key={item.id_pendaftaran_organisasi}
                    className={`verification-row${isSelected ? ' is-selected' : ''}`}
                    onClick={() => setSelectedId(item.id_pendaftaran_organisasi)}
                  >
                    <div>
                      <strong>{item.nama_organisasi}</strong>
                      <small>Sedang ditinjau</small>
                    </div>
                    <span>{item.nama_instansi}</span>
                    <span>{formatDate(item.tanggal_pendaftaran)}</span>
                    <StatusBadge status="menunggu" />
                  </button>
                )
              })
            )}
          </div>
        </section>

        <section className="verification-detail-card" aria-label="Detail pendaftar BEM">
          <header className="verification-detail-heading">
            <div>
              <h2>Detail pendaftar</h2>
              <p>Periksa data dan dokumen sebelum memutuskan</p>
            </div>
            {selectedDetail && (
              <span className="verification-detail-badge">
                ID: BEM-{selectedDetail.id_pendaftaran_organisasi}
              </span>
            )}
          </header>

          {detailLoading ? (
            <div className="verification-empty">Memuat detail pendaftar…</div>
          ) : !selectedDetail ? (
            <div className="verification-empty">Pilih salah satu pendaftaran untuk melihat detail.</div>
          ) : (
            <>
              <div className="verification-info-grid">
                <div>
                  <span>Nama organisasi</span>
                  <strong>{selectedDetail.nama_organisasi}</strong>
                </div>
                <div>
                  <span>Kampus</span>
                  <strong>{selectedDetail.nama_instansi}</strong>
                </div>
                <div>
                  <span>No. telepon</span>
                  <strong>{selectedDetail.nomor_telepon_organisasi || '—'}</strong>
                </div>
                <div>
                  <span>Penanggung jawab</span>
                  <strong>{selectedDetail.penanggung_jawab?.nama || '—'}</strong>
                </div>
                <div>
                  <span>NIM</span>
                  <strong>{selectedDetail.penanggung_jawab?.nim || '—'}</strong>
                </div>
                <div>
                  <span>No. WhatsApp</span>
                  <strong>{selectedDetail.penanggung_jawab?.nomor_whatsapp || '—'}</strong>
                </div>
              </div>

              <div className="verification-documents">
                <h3>Dokumen & Foto</h3>

                <button
                  type="button"
                  className="verification-doc-row"
                  onClick={() => handleOpenDoc('ktm', 'Foto selfie dengan KTM', 'image')}
                >
                  <span className="verification-file-icon is-image">
                    <FileTextIcon />
                  </span>
                  <span>
                    <strong>Foto selfie dengan KTM</strong>
                    <small>Terverifikasi kamera presensi</small>
                  </span>
                  <span className="verification-link" aria-label="Lihat foto selfie dengan KTM">
                    <EyeIcon />
                  </span>
                </button>

                <button
                  type="button"
                  className="verification-doc-row"
                  onClick={() => handleOpenDoc('sk-organisasi', 'SK Organisasi', 'pdf')}
                >
                  <span className="verification-file-icon is-pdf">PDF</span>
                  <span>
                    <strong>SK Organisasi</strong>
                    <small>Maks. 2 MB</small>
                  </span>
                  <span
                    className="verification-download"
                    role="button"
                    tabIndex={0}
                    aria-label="Unduh SK Organisasi"
                    onClick={(event) => handleDownloadDoc('sk-organisasi', 'SK_Organisasi.pdf', event)}
                    onKeyDown={(event) => { if (event.key === 'Enter' || event.key === ' ') handleDownloadDoc('sk-organisasi', 'SK_Organisasi.pdf', event) }}
                  >
                    <DownloadIcon />
                  </span>
                </button>

                <button
                  type="button"
                  className="verification-doc-row"
                  onClick={() => handleOpenDoc('sk-kepengurusan', 'SK Kepengurusan', 'pdf')}
                >
                  <span className="verification-file-icon is-pdf">PDF</span>
                  <span>
                    <strong>SK Kepengurusan</strong>
                    <small>Maks. 2 MB</small>
                  </span>
                  <span
                    className="verification-download"
                    role="button"
                    tabIndex={0}
                    aria-label="Unduh SK Kepengurusan"
                    onClick={(event) => handleDownloadDoc('sk-kepengurusan', 'SK_Kepengurusan.pdf', event)}
                    onKeyDown={(event) => { if (event.key === 'Enter' || event.key === ' ') handleDownloadDoc('sk-kepengurusan', 'SK_Kepengurusan.pdf', event) }}
                  >
                    <DownloadIcon />
                  </span>
                </button>
              </div>

              <div style={{ marginTop: '20px' }}>
                <label htmlFor="alasanPenolakanAdmin" className="verification-rejection-label">
                  <strong>Alasan penolakan</strong>
                  <span>Wajib diisi jika kamu menolak</span>
                </label>
                <textarea
                  id="alasanPenolakanAdmin"
                  className={`verification-rejection${rejectionError ? ' has-error' : ''}`}
                  placeholder="Tuliskan alasan penolakan jika berkas tidak sesuai…"
                  value={rejectionReason}
                  onChange={(e) => {
                    setRejectionReason(e.target.value)
                    if (rejectionError) setRejectionError('')
                  }}
                  disabled={actionLoading}
                />
                {rejectionError && <div className="verification-error">{rejectionError}</div>}
              </div>

              <div className="verification-actions">
                <button
                  type="button"
                  className="verification-button verification-button--approve"
                  onClick={handleApprove}
                  disabled={actionLoading}
                  style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: '8px' }}
                >
                  <CheckIcon />
                  <span>Setujui</span>
                </button>
                <button
                  type="button"
                  className="verification-button verification-button--reject"
                  onClick={handleReject}
                  disabled={actionLoading}
                  style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: '8px' }}
                >
                  <XIcon />
                  <span>Tolak</span>
                </button>
              </div>

              <p className="verification-detail-note">
                Hasil verifikasi akan tampil di halaman Status Registrasi pendaftar.
              </p>
            </>
          )}
        </section>
      </div>

      {confirmDialog && (
        <ConfirmationModal
          title={confirmDialog.title}
          message={confirmDialog.message}
          confirmLabel={confirmDialog.confirmLabel}
          confirmVariant={confirmDialog.confirmVariant}
          onConfirm={executeAction}
          onCancel={() => setConfirmDialog(null)}
        />
      )}

      {previewDoc && (
        <DocumentModal
          title={previewDoc.title}
          url={previewDoc.url}
          type={previewDoc.type}
          onClose={() => {
            if (previewDoc.url) URL.revokeObjectURL(previewDoc.url)
            setPreviewDoc(null)
          }}
        />
      )}

      <Toast message={toastMessage} onClose={() => setToastMessage('')} />
      </div>
    </main>
  )
}
