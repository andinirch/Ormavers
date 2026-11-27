import { useCallback, useEffect, useRef, useState } from 'react'
import AdminIcon from '../admin/AdminIcon.jsx'
import { assetUrl } from '../../api/api.js'
import { getUniversitas, searchOrganizations } from '../../services/orgApi.js'

function OrgLogo({ src, name }) {
  const [failed, setFailed] = useState(false)
  const initials = (name ?? '?').charAt(0).toUpperCase()
  const handleError = useCallback(() => setFailed(true), [])

  if (src && !failed) {
    return (
      <span className="libatkan-result__logo">
        <img src={assetUrl(src)} alt="" onError={handleError} />
      </span>
    )
  }

  return (
    <span className="libatkan-result__logo">
      <span className="libatkan-result__initials">{initials}</span>
    </span>
  )
}

const JENIS_FILTER_OPTIONS = [
  { value: '', label: 'Semua Jenis' },
  { value: 'bem', label: 'BEM' },
  { value: 'hmj', label: 'HMJ' },
  { value: 'ukm', label: 'UKM' },
]

function toParticipant(org, jenis = 'undangan') {
  return {
    id_organisasi: org.id,
    nama_organisasi: org.name,
    nama_universitas: org.nama_universitas ?? '',
    jenis_organisasi: org.organization_type ?? '',
    logo: org.logo ?? '',
    jenis,
  }
}


function UniversitasDropdown({ items, value, onChange, terkunci = false }) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef(null)
  const selected = (items || []).find((u) => String(u.value) === String(value))
  const displayLabel = selected ? selected.label : 'Semua Universitas'

  useEffect(() => {
    function handleClickOutside(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) setIsOpen(false)
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  return (
    <div className="event-category-dropdown" ref={containerRef}>
      <button
        type="button"
        className={`event-category-dropdown__trigger form-input${isOpen ? ' is-open' : ''}`}
        onClick={() => !terkunci && setIsOpen((o) => !o)}
        disabled={terkunci}
        aria-label="Filter universitas"
      >
        <span className="event-category-dropdown__label">{displayLabel}</span>
        <span className={`event-category-dropdown__chevron${isOpen ? ' is-open' : ''}`} aria-hidden="true">▾</span>
      </button>
      {isOpen && (
        <ul className="event-category-dropdown__menu" role="listbox">
          <li role="option" aria-selected={!value}
            className={`event-category-dropdown__item${!value ? ' is-selected' : ''}`}
            onClick={() => { onChange(''); setIsOpen(false) }}>Semua Universitas</li>
          {(items || []).map((u) => (
            <li key={u.value} role="option" aria-selected={String(u.value) === String(value)}
              className={`event-category-dropdown__item${String(u.value) === String(value) ? ' is-selected' : ''}`}
              onClick={() => { onChange(String(u.value)); setIsOpen(false) }}>{u.label}</li>
          ))}
        </ul>
      )}
    </div>
  )
}


function JenisDropdown({ options, value, onChange }) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef(null)
  const selected = options.find((o) => String(o.value) === String(value))

  useEffect(() => {
    function handleClickOutside(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) setIsOpen(false)
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  return (
    <div className="event-category-dropdown" ref={containerRef}>
      <button type="button" className={`event-category-dropdown__trigger form-input${isOpen ? ' is-open' : ''}`}
        onClick={() => setIsOpen((o) => !o)} aria-label="Filter jenis">
        <span className="event-category-dropdown__label">{selected ? selected.label : options[0].label}</span>
        <span className={`event-category-dropdown__chevron${isOpen ? ' is-open' : ''}`} aria-hidden="true">▾</span>
      </button>
      {isOpen && (
        <ul className="event-category-dropdown__menu" role="listbox">
          {options.map((o) => (
            <li key={o.value} role="option" aria-selected={String(o.value) === String(value)}
              className={`event-category-dropdown__item${String(o.value) === String(value) ? ' is-selected' : ''}`}
              onClick={() => { onChange(String(o.value)); setIsOpen(false) }}>{o.label}</li>
          ))}
        </ul>
      )}
    </div>
  )
}


const JENIS_ORG_OPTIONS = [
  { value: 'kolaborator', label: 'Kolaborator' },
  { value: 'undangan', label: 'Undangan' },
]

function JenisOrgDropdown({ terkunci = false, value, onChange, label }) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef(null)
  const selected = JENIS_ORG_OPTIONS.find((o) => o.value === value)

  useEffect(() => {
    function handleClickOutside(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) setIsOpen(false)
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  return (
    <div className="event-category-dropdown" ref={containerRef}>
      <button type="button" className={`event-category-dropdown__trigger form-input${isOpen ? ' is-open' : ''}`}
        onClick={() => !terkunci && setIsOpen((o) => !o)} disabled={terkunci} aria-label={label}>
        <span className={`event-category-dropdown__label${selected ? '' : ' is-placeholder'}`}>{terkunci ? 'Sudah diundang' : (selected ? selected.label : 'Pilih jenis')}</span>
        <span className={`event-category-dropdown__chevron${isOpen ? ' is-open' : ''}`} aria-hidden="true">▾</span>
      </button>
      {isOpen && (
        <ul className="event-category-dropdown__menu" role="listbox">
          {JENIS_ORG_OPTIONS.map((o) => (
            <li key={o.value} role="option" aria-selected={o.value === value}
              className={`event-category-dropdown__item${o.value === value ? ' is-selected' : ''}`}
              onClick={() => { onChange(o.value); setIsOpen(false) }}>{o.label}</li>
          ))}
        </ul>
      )}
    </div>
  )
}


const MASSAL_OPTIONS = [
  { value: 'kolaborator', label: 'Ubah semua jadi: Kolaborator' },
  { value: 'undangan', label: 'Ubah semua jadi: Undangan' },
  { value: 'hapus_semua', label: 'Hapus semua pilihan' },
]

function MassalDropdown({ onPick, disabled, count }) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef(null)

  useEffect(() => {
    function handleClickOutside(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) setIsOpen(false)
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  return (
    <div className="event-category-dropdown" ref={containerRef}>
      <button type="button" className={`event-category-dropdown__trigger form-input${isOpen ? ' is-open' : ''}`}
        onClick={() => !disabled && setIsOpen((o) => !o)} disabled={disabled} aria-label="Aksi massal">
        <span className="event-category-dropdown__label is-placeholder">Pilih aksi massal ({count} dipilih)</span>
        <span className={`event-category-dropdown__chevron${isOpen ? ' is-open' : ''}`} aria-hidden="true">▾</span>
      </button>
      {isOpen && (
        <ul className="event-category-dropdown__menu" role="listbox">
          {MASSAL_OPTIONS.map((o) => (
            <li key={o.value} role="option" className="event-category-dropdown__item"
              onClick={() => { onPick(o.value); setIsOpen(false) }}>{o.label}</li>
          ))}
        </ul>
      )}
    </div>
  )
}

function EventOrganizationPicker({ selected, onChange, jangkauan = '', universitasPembuat = null, organisasiPembuat = null, terundang = [], terundangNama = [] }) {
  const [universities, setUniversities] = useState([])
  const [keyword, setKeyword] = useState('')
  const [universitasId, setUniversitasId] = useState('')
  const [jenisFilter, setJenisFilter] = useState('')
  const [results, setResults] = useState([])
  const sudahTerundang = (id) => terundang.includes(String(id))
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    const run = async () => {
      try {
        const list = await getUniversitas()
        if (active) setUniversities(Array.isArray(list) ? list : [])
      } catch {
        if (active) setUniversities([])
      }
    }
    queueMicrotask(run)
    return () => { active = false }
  }, [])

  const runSearch = async (kw) => {
    setLoading(true)
    setError('')
    try {
      const list = await searchOrganizations({
        keyword: kw ?? keyword,
        id_universitas: universitasId || undefined,
        jenis_organisasi: jenisFilter || undefined,
      })
      const semua = Array.isArray(list) ? list : []
      const dibatasi = jangkauan === 'satu_universitas' && universitasPembuat
        ? semua.filter((org) => String(org.id_universitas ?? org.university_id ?? '') === String(universitasPembuat) && String(org.id_organisasi ?? org.id ?? '') !== String(organisasiPembuat ?? ''))
        : semua
      const tanpaTerundang = dibatasi.filter((org) => !sudahTerundang(org.id_organisasi ?? org.id) && !terundangNama.includes(String(org.name ?? org.nama_organisasi ?? '').trim().toLowerCase()))
      setResults(tanpaTerundang)
    } catch {
      setError('Daftar organisasi gagal dimuat.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    queueMicrotask(() => { runSearch('') })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [universitasId, jenisFilter, jangkauan, universitasPembuat])

  const selectedById = new Map(selected.map((item) => [String(item.id_organisasi), item]))
  const undanganCount = selected.filter((item) => item.jenis === 'undangan').length
  const kolaboratorCount = selected.filter((item) => item.jenis === 'kolaborator').length

  const chooseJenis = (org, jenis) => {
    if (!jenis) return
    if (selectedById.has(String(org.id))) {
      onChange(selected.map((item) => (
        String(item.id_organisasi) === String(org.id) ? { ...item, jenis } : item
      )))
    } else {
      onChange([...selected, toParticipant(org, jenis)])
    }
  }

  const removeOrganisasi = (id) => onChange(selected.filter((item) => String(item.id_organisasi) !== String(id)))

  const applyMassal = (action) => {
    if (!action) return
    if (action === 'hapus_semua') {
      onChange([])
      return
    }
    if (action === 'undangan' || action === 'kolaborator') {
      onChange(selected.map((item) => ({ ...item, jenis: action })))
    }
  }

  return (
    <div className="libatkan-section">
      <header className="libatkan-section__head">
        <div className="libatkan-section__title">
          <h2>Libatkan Organisasi <span className="event-form__optional">(opsional)</span></h2>
          <p>Kirim undangan kolaborasi atau partisipasi resmi ke ormawa mitra.</p>
        </div>
        <span className="libatkan-card__badge"><AdminIcon name="shield-check" size={14} /> Kolaborasi Terkelola</span>
      </header>

      <div className="libatkan-toolbar">
        <span className="libatkan-toolbar__search">
          <AdminIcon name="search" size={16} />
          <input
            type="search"
            placeholder="Cari organisasi..."
            value={keyword}
            onChange={(event) => setKeyword(event.target.value)}
            onKeyDown={(event) => { if (event.key === 'Enter') { event.preventDefault(); runSearch(keyword) } }}
          />
        </span>
        <UniversitasDropdown items={universities} value={universitasId} onChange={setUniversitasId} terkunci={jangkauan === 'satu_universitas' && Boolean(universitasPembuat)} />
        <JenisDropdown options={JENIS_FILTER_OPTIONS} value={jenisFilter} onChange={setJenisFilter} />
      </div>

      <div className="libatkan-summary">
        <span className="libatkan-summary__count">Terpilih (<strong>{selected.length}</strong>)</span>
        <span className="libatkan-chip is-undangan">{undanganCount} Undangan</span>
        <span className="libatkan-chip is-kolaborator">{kolaboratorCount} Kolaborator</span>
        <div className="libatkan-massal">
          <label htmlFor="libatkan-massal" className="libatkan-massal__label">Aksi massal:</label>
          <MassalDropdown onPick={applyMassal} disabled={selected.length === 0} count={selected.length} />
        </div>
      </div>

      <div className="libatkan-available">
        {loading && <p className="libatkan-state">Memuat…</p>}
        {!loading && error && <p className="libatkan-state is-error" role="alert">{error}</p>}
        {!loading && !error && results.length === 0 && <p className="libatkan-state">Tidak ada organisasi ditemukan.</p>}
        {!loading && !error && results.map((org) => {
          const chosen = selectedById.get(String(org.id))
          return (
            <div className={`libatkan-result${chosen ? ' is-selected' : ''}`} key={org.id}>
              <OrgLogo src={org.logo} name={org.name} />
              <span className="libatkan-result__body">
                <strong>{org.name}</strong>
                <small>{[org.organization_type, org.nama_universitas].filter(Boolean).join(' · ')}</small>
              </span>
              <span className="libatkan-result__actions">
                <JenisOrgDropdown terkunci={sudahTerundang(org.id_organisasi ?? org.id)} value={chosen?.jenis ?? ''} onChange={(v) => chooseJenis(org, v)} label={`Jenis keterlibatan ${org.name}`} />
                {chosen && (
                  <button
                    type="button"
                    className="libatkan-result__remove"
                    onClick={() => removeOrganisasi(org.id)}
                    aria-label={`Batalkan ${org.name}`}
                  >
                    <AdminIcon name="close" size={14} />
                  </button>
                )}
              </span>
            </div>
          )
        })}
      </div>

      {selected.length === 0 && <p className="libatkan-state">Belum ada organisasi dipilih.</p>}
    </div>
  )
}

export default EventOrganizationPicker
