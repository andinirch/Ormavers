import { useEffect, useMemo, useState } from 'react'
import AdminIcon from '../admin/AdminIcon.jsx'
import { assetUrl } from '../../api/api.js'
import { getAvailableOrganizations, inviteCollaborators, inviteGuests } from '../../api/eventInvitation.js'

const JENIS_META = {
  kolaborator: { title: 'Ajak Kolaborasi', submit: 'Kirim Ajakan' },
  undangan: { title: 'Undang Organisasi', submit: 'Kirim Undangan' },
}

const STATUS_LABEL = {
  menunggu: 'Menunggu',
  diterima: 'Diterima',
  ditolak: 'Ditolak',
  dibatalkan: 'Dibatalkan',
}

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const first = errors ? Object.values(errors).flat()[0] : null
  return first ?? error.response?.data?.message ?? fallback
}

function InviteOrganizationsModal({ open, eventId, jenis, onClose, onDone }) {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [selected, setSelected] = useState([])
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (!open) return undefined
    let active = true
    const run = async () => {
      setLoading(true)
      setError('')
      setSelected([])
      setSearch('')
      try {
        const result = await getAvailableOrganizations(eventId, jenis)
        if (active) setItems(Array.isArray(result?.data) ? result.data : [])
      } catch (requestError) {
        if (active) setError(errorMessage(requestError, 'Daftar organisasi gagal dimuat.'))
      } finally {
        if (active) setLoading(false)
      }
    }
    queueMicrotask(run)
    return () => { active = false }
  }, [open, eventId, jenis])

  const filtered = useMemo(() => {
    const keyword = search.trim().toLowerCase()
    if (!keyword) return items
    return items.filter((item) => String(item.nama_organisasi ?? '').toLowerCase().includes(keyword))
  }, [items, search])

  if (!open) return null

  const meta = JENIS_META[jenis] ?? JENIS_META.kolaborator

  const toggle = (id) => setSelected((current) => (current.includes(id) ? current.filter((x) => x !== id) : [...current, id]))

  const submit = async () => {
    if (!selected.length) { setError('Pilih minimal satu organisasi.'); return }
    setSubmitting(true)
    setError('')
    try {
      const fn = jenis === 'undangan' ? inviteGuests : inviteCollaborators
      const result = await fn(eventId, selected)
      onDone?.(result?.message ?? 'Berhasil dikirim.')
    } catch (requestError) {
      setError(errorMessage(requestError, 'Gagal mengirim.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div
      className="event-modal"
      role="presentation"
      onMouseDown={(event) => { if (event.target === event.currentTarget && !submitting) onClose() }}
    >
      <div className="event-modal__dialog" role="dialog" aria-modal="true" aria-label={meta.title}>
        <header className="event-modal__head">
          <h2>{meta.title}</h2>
          <button type="button" onClick={onClose} aria-label="Tutup" disabled={submitting}>
            <AdminIcon name="close" size={18} />
          </button>
        </header>

        <div className="event-modal__search">
          <AdminIcon name="search" size={16} />
          <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari organisasi" />
        </div>

        <div className="event-modal__body">
          {loading && <p className="event-modal__state">Memuat…</p>}
          {!loading && error && <p className="event-modal__state is-error">{error}</p>}
          {!loading && !error && filtered.length === 0 && <p className="event-modal__state">Tidak ada organisasi yang dapat dipilih.</p>}
          {!loading && !error && filtered.map((org) => {
            const disabled = !org.dapat_diajak
            const checked = selected.includes(org.id_organisasi)
            return (
              <label key={org.id_organisasi} className={`event-org-option${disabled ? ' is-disabled' : ''}`}>
                <input type="checkbox" checked={checked} disabled={disabled} onChange={() => toggle(org.id_organisasi)} />
                <span className="event-org-option__logo">
                  {org.logo
                    ? <img src={assetUrl(org.logo)} alt="" />
                    : <AdminIcon name="building" size={16} />}
                </span>
                <span className="event-org-option__body">
                  <strong>{org.nama_organisasi}</strong>
                  <small>
                    {[String(org.jenis_organisasi ?? '').toUpperCase(), org.universitas?.nama].filter(Boolean).join(' · ')}
                  </small>
                </span>
                {org.status_ajakan && org.status_ajakan !== 'belum_diajak' && (
                  <em className={`event-org-option__status is-${org.status_ajakan}`}>
                    {STATUS_LABEL[org.status_ajakan] ?? org.status_ajakan}
                  </em>
                )}
              </label>
            )
          })}
        </div>

        <footer className="event-modal__foot">
          {error && !loading && <span className="event-modal__error">{error}</span>}
          <button type="button" className="event-secondary-button" onClick={onClose} disabled={submitting}>Batal</button>
          <button type="button" className="event-primary-button" onClick={submit} disabled={submitting}>
            {submitting ? 'Mengirim…' : meta.submit}
          </button>
        </footer>
      </div>
    </div>
  )
}

export default InviteOrganizationsModal
