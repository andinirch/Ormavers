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

function InviteOrganizationsModal({
  open, eventId, jenis, jangkauan, universitasPembuat,
  organisasiPembuat, onClose, onDone,
}) {
  const [jenisAktif, setJenisAktif] = useState(jenis || 'kolaborator')
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [selected, setSelected] = useState([])
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (open) setJenisAktif(jenis || 'kolaborator')
  }, [open, jenis])

  useEffect(() => {
    if (!open) return undefined
    let active = true
    const run = async () => {
      setLoading(true)
      setError('')
      setSelected([])
      setSearch('')
      try {
        // Jangkauan publikasi tidak membatasi organisasi yang dapat diajak.
        const result = await getAvailableOrganizations(eventId, jenisAktif)
        const daftar = Array.isArray(result?.data) ? result.data : []
        const tanpaPembuat = daftar.filter((org) => {
          if (organisasiPembuat == null) return true
          const idOrg = org.id_organisasi ?? org.id
          return String(idOrg) !== String(organisasiPembuat)
        })

        if (active) setItems(tanpaPembuat)
      } catch (requestError) {
        if (active) setError(errorMessage(requestError, 'Daftar organisasi gagal dimuat.'))
      } finally {
        if (active) setLoading(false)
      }
    }
    run()
    return () => { active = false }
  }, [open, eventId, jenisAktif, jangkauan, universitasPembuat, organisasiPembuat])

  const filtered = useMemo(() => {
    const keyword = search.trim().toLowerCase()
    if (!keyword) return items
    return items.filter((item) => String(item.nama_organisasi ?? '').toLowerCase().includes(keyword))
  }, [items, search])

  if (!open) return null

  const meta = JENIS_META[jenisAktif] ?? JENIS_META.kolaborator

  const toggle = (id) => setSelected((current) => (current.includes(id) ? current.filter((x) => x !== id) : [...current, id]))

  const submit = async () => {
    if (!selected.length) { setError('Pilih minimal satu organisasi.'); return }
    setSubmitting(true)
    setError('')
    try {
      const fn = jenisAktif === 'undangan' ? inviteGuests : inviteCollaborators
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

        <div className={`event-invite-tabs${jenisAktif === 'undangan' ? ' is-undangan' : ''}`}>
          <span className="event-invite-tabs__blob" aria-hidden="true" />
          <button
            type="button"
            className={`event-invite-tabs__button${jenisAktif === 'kolaborator' ? ' is-active' : ''}`}
            onClick={() => { setJenisAktif('kolaborator'); setSelected([]); setError('') }}
            disabled={submitting || jenisAktif === 'kolaborator'}
            aria-pressed={jenisAktif === 'kolaborator'}
          >
            Ajak Kolaborasi
          </button>
          <button
            type="button"
            className={`event-invite-tabs__button${jenisAktif === 'undangan' ? ' is-active' : ''}`}
            onClick={() => { setJenisAktif('undangan'); setSelected([]); setError('') }}
            disabled={submitting || jenisAktif === 'undangan'}
            aria-pressed={jenisAktif === 'undangan'}
          >
            Kirim Undangan
          </button>
        </div>

        <div className="event-modal__search">
          <AdminIcon name="search" size={16} />
          <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari organisasi" />
        </div>

        <div className="event-modal__body">
          <div className="event-modal__loading-slot" aria-live="polite">
            {loading && <p className="event-modal__state">Memperbarui daftar organisasi…</p>}
          </div>
          {!loading && error && <p className="event-modal__state is-error">{error}</p>}
          {!loading && !error && filtered.length === 0 && <p className="event-modal__state">Tidak ada organisasi yang dapat dipilih.</p>}
          {!error && filtered.map((org) => {
            const disabled = !org.dapat_diajak
            const checked = selected.includes(org.id_organisasi)
            return (
              <label key={org.id_organisasi} className={`event-org-option${disabled ? ' is-disabled' : ''}`}>
                <input type="checkbox" checked={checked} disabled={loading || disabled} onChange={() => toggle(org.id_organisasi)} />
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
          <button type="button" className="event-primary-button event-modal__submit-button" onClick={submit} disabled={submitting}>
            {submitting ? 'Mengirim…' : meta.submit}
          </button>
        </footer>
      </div>
    </div>
  )
}

export default InviteOrganizationsModal
