import { useEffect, useState } from 'react'
import AdminIcon from '../admin/AdminIcon.jsx'
import { assetUrl } from '../../api/api.js'
import { getUniversitas, searchOrganizations } from '../../services/orgApi.js'

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

function EventOrganizationPicker({ selected, onChange }) {
  const [universities, setUniversities] = useState([])
  const [keyword, setKeyword] = useState('')
  const [universitasId, setUniversitasId] = useState('')
  const [jenisFilter, setJenisFilter] = useState('')
  const [results, setResults] = useState([])
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
      setResults(Array.isArray(list) ? list : [])
    } catch {
      setError('Daftar organisasi gagal dimuat.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    queueMicrotask(() => { runSearch('') })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [universitasId, jenisFilter])

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

  const applyMassal = (jenis) => {
    if (!jenis) return
    onChange(selected.map((item) => ({ ...item, jenis })))
  }

  return (
    <div className="libatkan-card">
      <header className="libatkan-card__head">
        <div className="libatkan-card__title">
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
        <select value={universitasId} onChange={(event) => setUniversitasId(event.target.value)} aria-label="Filter universitas">
          <option value="">Semua Universitas</option>
          {universities.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}
        </select>
        <select value={jenisFilter} onChange={(event) => setJenisFilter(event.target.value)} aria-label="Filter jenis">
          {JENIS_FILTER_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
        </select>
      </div>

      <div className="libatkan-summary">
        <span className="libatkan-summary__count">Terpilih (<strong>{selected.length}</strong>)</span>
        <span className="libatkan-chip is-undangan">{undanganCount} Undangan</span>
        <span className="libatkan-chip is-kolaborator">{kolaboratorCount} Kolaborator</span>
        <div className="libatkan-massal">
          <label htmlFor="libatkan-massal" className="libatkan-massal__label">Aksi massal:</label>
          <select
            id="libatkan-massal"
            className="libatkan-summary__massal"
            value=""
            onChange={(event) => applyMassal(event.target.value)}
            disabled={selected.length === 0}
          >
            <option value="" disabled>Pilih aksi</option>
            <option value="undangan">Jadikan semua: Undangan</option>
            <option value="kolaborator">Jadikan semua: Kolaborator</option>
          </select>
        </div>
      </div>

      <div className="libatkan-available">
        {loading && <p className="libatkan-state">Memuat…</p>}
        {!loading && error && <p className="libatkan-state is-error" role="alert">{error}</p>}
        {!loading && !error && results.length === 0 && <p className="libatkan-state">Tidak ada organisasi ditemukan.</p>}
        {!loading && !error && results.map((org) => {
          const chosen = selectedById.get(String(org.id))
          return (
            <div className="libatkan-result" key={org.id}>
              <span className="libatkan-result__logo">
                {org.logo ? <img src={assetUrl(org.logo)} alt="" /> : <AdminIcon name="building" size={16} />}
              </span>
              <span className="libatkan-result__body">
                <strong>{org.name}</strong>
                <small>{[org.organization_type, org.nama_universitas].filter(Boolean).join(' · ')}</small>
              </span>
              <span className="libatkan-result__actions">
                <select
                  className="libatkan-result__jenis"
                  value={chosen?.jenis ?? ''}
                  onChange={(event) => chooseJenis(org, event.target.value)}
                  aria-label={`Jenis keterlibatan ${org.name}`}
                >
                  <option value="" disabled>Pilih jenis</option>
                  <option value="kolaborator">Kolaborator</option>
                  <option value="undangan">Undangan</option>
                </select>
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
