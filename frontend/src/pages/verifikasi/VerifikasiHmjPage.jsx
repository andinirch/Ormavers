import { useEffect, useState } from 'react'
import {
  approveMemberRequest,
  getMemberRequestDetail,
  listMemberRequests,
  getMemberKtm,
  rejectMemberRequest,
} from '../../services/verification/verificationApi.js'
import {
  ConfirmationModal,
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

function formatRequestDate(value) {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
}

function ShieldUserIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
      <circle cx="12" cy="7" r="4" />
    </svg>
  )
}

export default function VerifikasiHmjPage() {
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
  const [ktmPreviewUrl, setKtmPreviewUrl] = useState(null)

  const loadData = async () => {
    setLoading(true)
    const res = await listMemberRequests()
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

  useEffect(() => {
    queueMicrotask(loadData)
  }, [])

  useEffect(() => {
    if (!selectedId) {
      queueMicrotask(() => setSelectedDetail(null))
      return undefined
    }
    let cancelled = false
    const fetchDetail = async () => {
        setDetailLoading(true)
      const res = await getMemberRequestDetail(selectedId)
      if (!cancelled) {
        setSelectedDetail(res.data ?? null)
        getMemberKtm(selectedId).then((response) => {
          if (cancelled) return
          const url = URL.createObjectURL(response.data)
          setKtmPreviewUrl((previous) => {
            if (previous) URL.revokeObjectURL(previous)
            return url
          })
        }).catch(() => {
          if (!cancelled) setKtmPreviewUrl(null)
        })
        setRejectionReason('')
        setRejectionError('')
        setDetailLoading(false)
      }
    }
    queueMicrotask(fetchDetail)
    return () => {
      cancelled = true
      setKtmPreviewUrl((previous) => {
        if (previous) URL.revokeObjectURL(previous)
        return null
      })
    }
  }, [selectedId])

  const handleApprove = () => {
    setConfirmDialog({
      type: 'approve',
      title: 'Setujui Permintaan Anggota',
      message: `Apakah Anda yakin ingin menyetujui pendaftaran ${selectedDetail?.namaLengkap}? Keanggotaan mahasiswa akan aktif di organisasi HMJ/UKM Anda.`,
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
      title: 'Tolak Permintaan Anggota',
      message: `Apakah Anda yakin ingin menolak pendaftaran ${selectedDetail?.namaLengkap}?`,
      confirmLabel: 'Tolak',
      confirmVariant: 'reject',
    })
  }

  const executeAction = async () => {
    if (!confirmDialog || !selectedId) return
    setActionLoading(true)
    try {
      if (confirmDialog.type === 'approve') {
        await approveMemberRequest(selectedId)
        setToastMessage('Permintaan keanggotaan berhasil disetujui (mock).')
      } else {
        await rejectMemberRequest(selectedId, rejectionReason.trim())
        setToastMessage('Permintaan keanggotaan telah ditolak (mock).')
      }
      setConfirmDialog(null)
      setRejectionReason('')
      await loadData()
    } finally {
      setActionLoading(false)
    }
  }

  const pendingCount = items.filter((it) => it.status === 'menunggu').length

  return (
    <div className="verification-page verification-hmj-page">
      <div className="verification-page__heading">
        <div className="verification-eyebrow">
          <span>Portal Verifikasi</span>
          <span style={{ opacity: 0.5 }}>/</span>
          <span>Keanggotaan Organisasi</span>
        </div>
        <h1>Daftar permintaan anggota</h1>
        <p>Verifikasi permintaan keanggotaan di organisasi kamu</p>
      </div>

      <div className="verification-layout">
        <section className="verification-list-card" aria-label="Daftar permintaan keanggotaan">
          <header className="verification-card-heading">
            <h2>
              <span className="verification-accent" aria-hidden="true" style={{ display: 'none' }} />
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: '8px' }}>
                <ShieldUserIcon />
                Permintaan Keanggotaan
              </span>
            </h2>
            <span className="verification-count">
              {loading ? 'Memuat…' : `${pendingCount} Permintaan Baru`}
            </span>
          </header>

          <div className="verification-list verification-list--member">
            <div className="verification-list__header">
              <span>Nama</span>
              <span>NIM</span>
              <span>Tanggal</span>
              <span>Status</span>
            </div>

            {loading ? (
              <div className="verification-empty">Memuat daftar permintaan…</div>
            ) : items.length === 0 ? (
              <div className="verification-empty">
                <strong>Tidak ada permintaan anggota menunggu</strong>
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
                    <span className="verification-member">
                      <b>{item.initials}</b>
                      <strong>{item.namaLengkap}</strong>
                    </span>
                    <span>{item.nim}</span>
                       <span>{formatRequestDate(item.tanggal)}</span>
                    <em style={{ textTransform: 'capitalize' }}>{item.status}</em>
                  </button>
                )
              })
            )}
          </div>
        </section>

        <section className="verification-detail-card" aria-label="Detail pendaftar anggota">
          <header className="verification-detail-heading">
            <div>
              <h2>Detail pendaftar</h2>
              <p>Periksa data dan dokumen sebelum memutuskan</p>
            </div>
            <span className="verification-detail-badge">
              • Menunggu Review
            </span>
          </header>

          {detailLoading ? (
            <div className="verification-empty">Memuat detail…</div>
          ) : !selectedDetail ? (
            <div className="verification-empty">Pilih salah satu calon anggota untuk melihat detail.</div>
          ) : (
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
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
                  <h3 style={{ margin: 0, fontSize: '15px' }}>Foto KTM Mahasiswa</h3>
                  <small style={{ color: 'var(--v-muted)', fontSize: '13px' }}>Verifikasi Identitas</small>
                </div>

                 <div
                   className="verification-ktm"
                   style={{ cursor: 'pointer', padding: '14px', borderRadius: '14px', rowGap: '4px' }}
                   onClick={() => {
                     if (ktmPreviewUrl) window.open(ktmPreviewUrl, '_blank', 'noopener,noreferrer')
                   }}
                 >
                   <div className="verification-ktm__image" style={{ height: '175px', marginBottom: 0, background: 'transparent', overflow: 'hidden', position: 'relative' }}>
                     {ktmPreviewUrl ? (
                       <img src={ktmPreviewUrl} alt={`Foto KTM ${selectedDetail.namaLengkap}`} style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: '10px' }} />
                     ) : (
                       <div>
                         <small style={{ display: 'block', opacity: 0.85, fontSize: '12px' }}>Foto KTM tidak tersedia</small>
                         <strong style={{ fontSize: '20px' }}>{selectedDetail.namaLengkap}</strong>
                       </div>
                     )}
                     <span style={{ position: 'absolute', left: '14px', bottom: '14px', display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '5px 9px', color: '#fff', background: 'rgba(15, 23, 42, 0.62)', borderRadius: '6px', fontSize: '12px' }}>
                       <span aria-hidden="true">▣</span>
                       Kartu Tanda Mahasiswa Aktif
                     </span>
                   </div>
                   <div style={{ display: 'flex', justifyContent: 'space-between', width: '100%', alignItems: 'center', marginTop: '2px', gap: '12px' }}>
                     <div style={{ minWidth: 0 }}>
                       <strong style={{ display: 'block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{selectedDetail.fileName}</strong>
                       <small>{selectedDetail.fileSize}</small>
                     </div>
                     <span className="verification-link" style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', marginLeft: 'auto', flexShrink: 0 }}><EyeIcon /> Perbesar</span>
                   </div>
                 </div>
              </div>

              <div style={{ marginTop: '20px' }}>
                <label htmlFor="alasanPenolakanHmj" className="verification-rejection-label">
                  <strong>Alasan penolakan</strong>
                  <span>Wajib diisi jika kamu menolak</span>
                </label>
                <textarea
                  id="alasanPenolakanHmj"
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


      <Toast message={toastMessage} onClose={() => setToastMessage('')} />
    </div>
  )
}
