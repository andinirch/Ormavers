import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import {
  approveMockRegistration,
  getMockRegistrationDetail,
  listMockRegistrations,
  rejectMockRegistration,
} from '../../services/verification/verificationApi.js'
import {
  ConfirmationModal,
  DocumentModal,
  Toast,
} from '../../components/verification/VerificationPanel.jsx'

function EyeIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
      <circle cx="12" cy="12" r="3" />
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

function InfoIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" style={{ flexShrink: 0, marginTop: '2px' }}>
      <circle cx="12" cy="12" r="10" />
      <line x1="12" y1="16" x2="12" y2="12" />
      <line x1="12" y1="8" x2="12.01" y2="8" />
    </svg>
  )
}

export default function VerifikasiBemPage() {
  const [searchParams] = useSearchParams()
  const bagian = searchParams.get('bagian') === 'anggota' ? 'anggota' : 'hmj'
  const isMemberMode = bagian === 'anggota'

  const [items, setItems] = useState([])
  const [selectedId, setSelectedId] = useState(null)
  const [selectedDetail, setSelectedDetail] = useState(null)
  const [loading, setLoading] = useState(true)
  const [detailLoading, setDetailLoading] = useState(false)
  const [actionLoading, setActionLoading] = useState(false)
  const [rejectionReason, setRejectionReason] = useState('')
  const [rejectionError, setRejectionError] = useState('')
  const [toastMessage, setToastMessage] = useState('')
  const [confirmDialog, setConfirmDialog] = useState(null)
  const [previewDoc, setPreviewDoc] = useState(null)

  const [refreshIndex, setRefreshIndex] = useState(0)

  useEffect(() => {
    let cancelled = false
    const run = async () => {
      setLoading(true)
      const typeKey = bagian === 'anggota' ? 'member-bem' : 'hmj'
      const res = await listMockRegistrations(typeKey)
      if (cancelled) return
      const list = res.data ?? []
      setItems(list)
      if (list.length > 0) {
        setSelectedId((current) => (current && list.some((it) => it.id === current) ? current : list[0].id))
      } else {
        setSelectedId(null)
        setSelectedDetail(null)
      }
      setLoading(false)
    }
    queueMicrotask(run)
    return () => {
      cancelled = true
    }
  }, [bagian, refreshIndex])

  useEffect(() => {
    if (!selectedId) {
      queueMicrotask(() => setSelectedDetail(null))
      return
    }
    let cancelled = false
    const fetchDetail = async () => {
      setDetailLoading(true)
      const typeKey = isMemberMode ? 'member-bem' : 'hmj'
      const res = await getMockRegistrationDetail(typeKey, selectedId)
      if (!cancelled) {
        setSelectedDetail(res.data ?? null)
        setRejectionReason('')
        setRejectionError('')
        setDetailLoading(false)
      }
    }
    queueMicrotask(fetchDetail)
    return () => {
      cancelled = true
    }
  }, [selectedId, isMemberMode])

  const handleApprove = () => {
    const targetName = isMemberMode ? selectedDetail?.namaLengkap : selectedDetail?.namaOrganisasi
    setConfirmDialog({
      type: 'approve',
      title: isMemberMode ? 'Setujui Permintaan Anggota' : 'Setujui Registrasi HMJ/UKM',
      message: `Apakah Anda yakin ingin menyetujui ${targetName}?`,
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
    const targetName = isMemberMode ? selectedDetail?.namaLengkap : selectedDetail?.namaOrganisasi
    setConfirmDialog({
      type: 'reject',
      title: isMemberMode ? 'Tolak Permintaan Anggota' : 'Tolak Registrasi HMJ/UKM',
      message: `Apakah Anda yakin ingin menolak ${targetName}?`,
      confirmLabel: 'Tolak',
      confirmVariant: 'reject',
    })
  }

  const executeAction = async () => {
    if (!confirmDialog || !selectedId) return
    setActionLoading(true)
    const typeKey = isMemberMode ? 'member-bem' : 'hmj'
    try {
      if (confirmDialog.type === 'approve') {
        await approveMockRegistration(typeKey, selectedId)
        setToastMessage(`${isMemberMode ? 'Permintaan anggota' : 'Registrasi HMJ/UKM'} berhasil disetujui (mock).`)
      } else {
        await rejectMockRegistration(typeKey, selectedId, rejectionReason.trim())
        setToastMessage(`${isMemberMode ? 'Permintaan anggota' : 'Registrasi HMJ/UKM'} telah ditolak (mock).`)
      }
      setConfirmDialog(null)
      setRejectionReason('')
      setRefreshIndex((idx) => idx + 1)
    } finally {
      setActionLoading(false)
    }
  }

  const pendingCount = items.filter((it) => it.status === 'menunggu').length

  return (
    <div className="verification-page verification-bem-page">
      <div className="verification-page__heading">
        <h1>{isMemberMode ? 'Daftar permintaan anggota' : 'Daftar registrasi HMJ/UKM'}</h1>
        <p>
          {isMemberMode
            ? 'Verifikasi permintaan keanggotaan di organisasi BEM kamu'
            : 'Verifikasi pendaftaran HMJ/UKM yang memilih BEM kamu sebagai induk'}
        </p>
      </div>

      <div className="verification-layout">
        <section className="verification-list-card" aria-label={isMemberMode ? 'Daftar permintaan anggota' : 'Daftar permohonan registrasi HMJ/UKM'}>
          <header className="verification-card-heading">
            <h2>
              <span className="verification-accent" aria-hidden="true" />
              {isMemberMode ? 'Permintaan Keanggotaan' : 'Permohonan Registrasi HMJ/UKM'}
            </h2>
            <span className="verification-count">
              {loading ? 'Memuat…' : `${pendingCount} Menunggu Tindakan`}
            </span>
          </header>

          <div className={`verification-list ${isMemberMode ? 'verification-list--member' : 'verification-list--hmj'}`}>
            <div className="verification-list__header">
              {isMemberMode ? (
                <>
                  <span>Nama</span>
                  <span>NIM</span>
                  <span>Tanggal</span>
                  <span>Status</span>
                </>
              ) : (
                <>
                  <span>Nama Organisasi</span>
                  <span>Jenis</span>
                  <span>Tanggal</span>
                </>
              )}
            </div>

            {loading ? (
              <div className="verification-empty">Memuat daftar…</div>
            ) : items.length === 0 ? (
              <div className="verification-empty">
                <strong>Tidak ada pendaftaran menunggu</strong>
              </div>
            ) : (
              items.map((item) => {
                const isSelected = item.id === selectedId
                return (
                  <button
                    type="button"
                    key={item.id}
                    className={`verification-row${isSelected ? ' is-selected' : ''}`}
                    onClick={() => setSelectedId(item.id)}
                  >
                    {isMemberMode ? (
                      <>
                        <span className="verification-member">
                          <b>{item.initials}</b>
                          <strong>{item.namaLengkap}</strong>
                        </span>
                        <span>{item.nim}</span>
                        <span>{item.tanggal}</span>
                        <em style={{ textTransform: 'capitalize' }}>{item.status}</em>
                      </>
                    ) : (
                      <>
                        <span className="verification-org">
                          <b>{item.initials}</b>
                          <span>
                            <strong>{item.namaOrganisasi}</strong>
                          </span>
                        </span>
                        <em>{item.jenis}</em>
                        <span>{item.tanggal}</span>
                      </>
                    )}
                  </button>
                )
              })
            )}
          </div>

          {!isMemberMode && (
            <div className="verification-info-note">
              <InfoIcon />
              <span>
                Pendaftaran yang disetujui akan resmi terdaftar di bawah binaan Badan Eksekutif Mahasiswa
              </span>
            </div>
          )}
        </section>

        <section className="verification-detail-card" aria-label="Detail pendaftar">
          <header className="verification-detail-heading">
            <div>
              <h2>Detail pendaftar</h2>
              <p>Periksa data dan dokumen sebelum memutuskan</p>
            </div>
            <span className="verification-detail-badge">Aktif Ditinjau</span>
          </header>

          {detailLoading ? (
            <div className="verification-empty">Memuat detail…</div>
          ) : !selectedDetail ? (
            <div className="verification-empty">Pilih salah satu pendaftaran untuk melihat detail.</div>
          ) : isMemberMode ? (
            <>
              <div className="verification-info-grid">
                <div>
                  <span>Nama Lengkap</span>
                  <strong>{selectedDetail.namaLengkap}</strong>
                </div>
                <div>
                  <span>NIM</span>
                  <strong>{selectedDetail.nim}</strong>
                </div>
              </div>

              <div className="verification-documents">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                  <h3 style={{ margin: 0 }}>Foto KTM Mahasiswa</h3>
                  <small style={{ color: 'var(--v-muted)' }}>Verifikasi Identitas</small>
                </div>
                <div className="verification-ktm" style={{ cursor: 'pointer' }} onClick={() => setPreviewDoc({ title: `Foto KTM — ${selectedDetail.namaLengkap}`, type: 'image' })}>
                  <div className="verification-ktm__image">
                    <div>
                      <small style={{ display: 'block', opacity: 0.85 }}>Kartu Tanda Mahasiswa Aktif</small>
                      <strong>{selectedDetail.namaLengkap}</strong>
                    </div>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', width: '100%', alignItems: 'center', marginTop: '6px' }}>
                    <div>
                      <strong>{selectedDetail.fileName}</strong>
                      <small>{selectedDetail.fileDate} • {selectedDetail.fileSize}</small>
                    </div>
                    <span className="verification-link" style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                      <EyeIcon /> Perbesar
                    </span>
                  </div>
                </div>
              </div>

              <div style={{ marginTop: '20px' }}>
                <label htmlFor="alasanPenolakanMember" className="verification-rejection-label">
                  <strong>Alasan penolakan</strong>
                  <span>Wajib diisi jika kamu menolak</span>
                </label>
                <textarea
                  id="alasanPenolakanMember"
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

              <div className="verification-actions is-member">
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
              </div>

              <p className="verification-detail-note">
                Hasil verifikasi akan tampil di halaman Status Registrasi pendaftar.
              </p>
            </>
          ) : (
            <>
              <div className="verification-info-grid">
                <div>
                  <span>Nama organisasi</span>
                  <strong>{selectedDetail.namaOrganisasi}</strong>
                </div>
                <div>
                  <span>Jenis</span>
                  <strong>{selectedDetail.jenisLengkap}</strong>
                </div>
                <div>
                  <span>No. telepon</span>
                  <strong>{selectedDetail.nomorTelepon}</strong>
                </div>
                <div>
                  <span>Penanggung jawab</span>
                  <strong>{selectedDetail.penanggungJawab}</strong>
                </div>
                <div>
                  <span>NIM</span>
                  <strong>{selectedDetail.nim}</strong>
                </div>
                <div>
                  <span>No. WhatsApp</span>
                  <strong>{selectedDetail.whatsapp}</strong>
                </div>
              </div>

              <div className="verification-documents">
                <h3 style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z" />
                  </svg>
                  Dokumen & Foto Terlampir
                </h3>

                <button
                  type="button"
                  className="verification-doc-row"
                  onClick={() => setPreviewDoc({ title: 'Foto selfie dengan KTM', fileName: 'KTM-DIMAS-BAYU-2022510045.jpg', type: 'image' })}
                >
                  <span className="verification-file-icon is-image">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                      <rect width="18" height="18" x="3" y="3" rx="2" />
                      <circle cx="9" cy="9" r="2" />
                      <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                    </svg>
                  </span>
                  <span>
                    <strong>Foto selfie dengan KTM</strong>
                    <small>KTM-DIMAS-BAYU-2022510045.jpg</small>
                  </span>
                  <span className="verification-link" aria-label="Lihat foto selfie dengan KTM">
                    <EyeIcon />
                  </span>
                </button>

                <div style={{ marginTop: '16px', marginBottom: '6px' }}>
                  <small style={{ fontWeight: 700, color: 'var(--v-navy)', letterSpacing: '0.04em' }}>SURAT KEPUTUSAN (SK)</small>
                </div>

                <button
                  type="button"
                  className="verification-doc-row"
                  onClick={() => setPreviewDoc({ title: 'SK Organisasi', fileName: 'SK_Organisasi_HMIF_2026.pdf', type: 'pdf' })}
                >
                  <span className="verification-file-icon is-pdf">PDF</span>
                  <span>
                    <strong>SK Organisasi</strong>
                    <small>Maks. 2 MB</small>
                  </span>
                  <span className="verification-download" aria-label="Unduh SK Organisasi">
                    <DownloadIcon />
                  </span>
                </button>

                <button
                  type="button"
                  className="verification-doc-row"
                  onClick={() => setPreviewDoc({ title: 'SK Kepengurusan', fileName: 'SK_Kepengurusan_HMIF_2026.pdf', type: 'pdf' })}
                >
                  <span className="verification-file-icon is-pdf">PDF</span>
                  <span>
                    <strong>SK Kepengurusan</strong>
                    <small>Maks. 2 MB</small>
                  </span>
                  <span className="verification-download" aria-label="Unduh SK Kepengurusan">
                    <DownloadIcon />
                  </span>
                </button>
              </div>

              <div style={{ marginTop: '20px' }}>
                <label htmlFor="alasanPenolakanBem" className="verification-rejection-label">
                  <strong>Alasan penolakan</strong>
                  <span>Wajib diisi jika kamu menolak</span>
                </label>
                <textarea
                  id="alasanPenolakanBem"
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
          fileName={previewDoc.fileName}
          type={previewDoc.type}
          onClose={() => setPreviewDoc(null)}
        />
      )}

      <Toast message={toastMessage} onClose={() => setToastMessage('')} />
    </div>
  )
}
