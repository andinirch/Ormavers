import { useEffect, useState } from 'react'
import { decideVerification, getVerificationList } from '../../services/verification/verificationApi.js'
import { Icon } from '../../layouts/VerificationShell.jsx'

const statusLabels = { menunggu: 'Menunggu', disetujui: 'Disetujui', ditolak: 'Ditolak' }
function StatusBadge({ status }) { return <span className={`verification-badge verification-badge--${status}`}><i />{statusLabels[status]}</span> }
function formatCount(items) { return items.filter((item) => item.status === 'menunggu').length }

function PreviewModal({ document, onClose }) {
  if (!document) return null
  return <div className="verification-modal-backdrop" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget) onClose() }}>
    <section className="verification-modal" role="dialog" aria-modal="true" aria-labelledby="preview-title">
      <button className="verification-modal__close" type="button" onClick={onClose} aria-label="Tutup pratinjau">×</button>
      <h2 id="preview-title">Pratinjau dokumen</h2>
      <div className={`verification-document-preview${document.kind === 'photo' ? ' is-photo' : ''}`}>
        {document.kind === 'photo' ? <><div className="verification-photo-placeholder"><span>{document.initials || 'KTM'}</span></div><small>Foto dokumen demo</small></> : <><strong>PDF</strong><span>{document.name}</span><small>Dokumen contoh - pratinjau demo</small></>}
      </div>
    </section>
  </div>
}

function DetailInfo({ item, member }) {
  const fields = member ? [['Nama Lengkap', item.namaLengkap], ['NIM', item.nim]] : [['Nama organisasi', item.namaOrganisasi], ...(item.jenis ? [['Jenis', item.jenis === 'HMJ' ? 'HMJ (Himpunan Mahasiswa Jurusan)' : 'UKM']] : []), ...(item.kampus ? [['Kampus', item.kampus]] : []), ['No. telepon', item.nomorTelepon], ['Penanggung jawab', item.penanggungJawab], ['NIM', item.nim], ['No. WhatsApp', item.whatsapp]]
  return <div className="verification-info-grid">{fields.map(([label, value]) => <div key={label}><span>{label}</span><strong>{value}</strong></div>)}</div>
}

function VerificationPage({ type, member = false, title, subtitle, eyebrow, listTitle, note, selectedBadge, renderRow, renderDocs, roleNote }) {
  const [items, setItems] = useState([])
  const [selectedId, setSelectedId] = useState(null)
  const [reason, setReason] = useState('')
  const [reasonError, setReasonError] = useState('')
  const [busy, setBusy] = useState(false)
  const [toast, setToast] = useState('')
  const [preview, setPreview] = useState(null)
  useEffect(() => { getVerificationList(type).then((data) => { setItems(data); setSelectedId(data[0]?.id ?? null) }) }, [type])
  const selected = items.find((item) => item.id === selectedId)
  const choose = (id) => { setSelectedId(id); setReason(''); setReasonError(''); setToast('') }
  const decide = async (status) => {
    if (!selected || selected.status !== 'menunggu' || busy) return
    if (status === 'ditolak' && !reason.trim()) { setReasonError('Alasan penolakan wajib diisi.'); return }
    setBusy(true); setReasonError('')
    const result = await decideVerification(type, selected.id, status, reason.trim())
    setBusy(false)
    if (!result.ok) { setToast(result.error); return }
    setItems((current) => current.map((item) => item.id === selected.id ? result.data : item))
    setToast(status === 'disetujui' ? `Status diperbarui menjadi Disetujui dalam simulasi.${member ? ' Notifikasi WhatsApp belum dikirim.' : ''}` : 'Status penolakan diperbarui dalam simulasi. Penyimpanan backend belum dilakukan.')
  }
  const openPreview = (doc) => setPreview(doc)
  return <div className="verification-page">
    <div className="verification-page__heading"><div className="verification-eyebrow"><Icon name="shield" size={17} />{eyebrow}</div><h1>{title}</h1><p>{subtitle}</p></div>
    <div className="verification-layout">
      <section className="verification-list-card">
        <div className="verification-card-heading"><div><h2><span className="verification-accent" />{listTitle}</h2></div><span className="verification-count">{formatCount(items)} {member ? 'Permintaan Baru' : type === 'bem' ? 'Pengajuan Masuk' : 'Menunggu Tindakan'}</span></div>
        <div className={`verification-list verification-list--${member ? 'member' : type}`}>
          <div className="verification-list__header">{member ? <><span>Nama</span><span>NIM</span><span>Tanggal</span><span>Status</span></> : type === 'bem' ? <><span>Nama BEM</span><span>Kampus</span><span>Tanggal</span><span>Status</span></> : <><span>Nama Organisasi</span><span>Jenis</span><span>Tanggal</span></>}</div>
          {items.map((item) => <button type="button" key={item.id} className={`verification-row${item.id === selectedId ? ' is-selected' : ''}`} onClick={() => choose(item.id)}>{renderRow(item)}<StatusBadge status={item.status} /></button>)}
        </div>
        {note && <div className="verification-info-note"><Icon name="shield" size={18} /><span>{note}</span></div>}
      </section>
      <section className="verification-detail-card">
        <div className="verification-detail-heading"><div><h2>Detail pendaftar</h2><p>Periksa data dan dokumen sebelum memutuskan</p></div><span className="verification-detail-badge">{selectedBadge || (selected ? statusLabels[selected.status] : 'Menunggu Review')}</span></div>
        {selected ? <>
          <DetailInfo item={selected} member={member} />
          {renderDocs(selected, openPreview)}
          <div className="verification-rejection-label"><strong>Alasan penolakan</strong><span>Wajib diisi jika kamu menolak</span></div>
          <textarea className={`verification-rejection${reasonError ? ' has-error' : ''}`} value={selected.status === 'ditolak' ? (selected.alasanPenolakan || '') : reason} onChange={(event) => setReason(event.target.value)} disabled={selected.status !== 'menunggu' || busy} placeholder="Tuliskan alasan penolakan jika berkas tidak sesuai..." aria-invalid={Boolean(reasonError)} />
          {reasonError && <p className="verification-error" role="alert">{reasonError}</p>}
          {selected.status === 'menunggu' ? <div className={`verification-actions${member ? ' is-member' : ''}`}><button type="button" className="verification-button verification-button--approve" disabled={busy} onClick={() => decide('disetujui')}>{busy ? 'Memproses...' : '✓  Setujui'}</button><button type="button" className="verification-button verification-button--reject" disabled={busy} onClick={() => decide('ditolak')}>×  Tolak</button></div> : <div className="verification-result">Keputusan sudah diperbarui dalam sesi simulasi.</div>}
          <p className="verification-detail-note">{roleNote || 'Hasil verifikasi akan tampil di halaman Status Registrasi pendaftar.'}</p>
        </> : <div className="verification-empty">Tidak ada data pengajuan.</div>}
      </section>
    </div>
    {toast && <div className="verification-toast" role="status">{toast}<button type="button" onClick={() => setToast('')} aria-label="Tutup notifikasi">×</button></div>}
    <PreviewModal document={preview} onClose={() => setPreview(null)} />
  </div>
}

function DocRows({ item, onPreview, member = false }) {
  if (member) {
    return <div className="verification-documents">
      <h3>Foto KTM Mahasiswa</h3>
      <button type="button" className="verification-ktm" onClick={() => onPreview({ kind: 'photo', name: item.file, initials: item.initials })}>
        <div className="verification-ktm__image"><span>KARTU TANDA MAHASISWA</span><strong>{item.initials}</strong></div>
        <div><strong>{item.file}</strong><small>Terunggah {item.tanggal} • 1.4 MB</small></div>
        <span className="verification-link">◉ Perbesar</span>
      </button>
    </div>
  }

  const initials = item.penanggungJawab?.split(' ').map((part) => part[0]).join('').slice(0, 2)
  return <div className="verification-documents">
    <h3>Dokumen & Foto</h3>
    <button type="button" className="verification-doc-row" onClick={() => onPreview({ kind: 'photo', name: 'Foto selfie dengan KTM', initials })}>
      <span className="verification-file-icon is-image">▣</span><span><strong>Foto selfie dengan KTM</strong><small>Terverifikasi kamera presensi</small></span><Icon name="profile" />
    </button>
    {item.dokumen.map((name) => <button type="button" className="verification-doc-row" key={name} onClick={() => onPreview({ kind: 'pdf', name })}>
      <span className="verification-file-icon is-pdf">PDF</span><span><strong>{name}</strong><small>{name === 'SK Organisasi' ? '2.4 MB' : '1.8 MB'} • Dokumen Resmi</small></span><span className="verification-download">↓</span>
    </button>)}
  </div>
}

export { VerificationPage, DocRows }
