import { useEffect, useState } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import Swal from 'sweetalert2'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import { getBidangOrganisasi } from '../api/organizationManagement.js'
import { getDirectoryOrganizations, searchOrganizations } from '../services/orgApi.js'
import '../styles/organization-directory.css'

function BackIcon() {
  return <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
}

function CollaborationIcon() {
  return <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.07.07l2-2a5 5 0 0 0-7.07-7.07l-1.15 1.15" /><path d="M14 11a5 5 0 0 0-7.07-.07l-2 2A5 5 0 0 0 12 20l1.15-1.15" /></svg>
}

function OrganizationListPage() {
  const { pathname } = useLocation()
  const { universityId } = useParams()
  const directoryPath = pathname.startsWith('/superadmin/')
    ? '/superadmin/direktori'
    : pathname.startsWith('/admin/')
      ? '/admin/direktori'
      : pathname.startsWith('/verifikasi/')
        ? '/verifikasi/direktori'
        : '/direktori'
  const organizationPath = (organizationId) => `${directoryPath}/universitas/${universityId}/organisasi/${organizationId}`
  const [university, setUniversity] = useState(null)
  const [organizations, setOrganizations] = useState([])
  const [type, setType] = useState('')
  const [field, setField] = useState('')
  const [fields, setFields] = useState([])
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    Promise.all([getDirectoryOrganizations(universityId), getBidangOrganisasi()])
      .then(([result, fieldResult]) => {
        if (!active) return
        const fieldData = fieldResult?.data
        const fieldItems = Array.isArray(fieldData) ? fieldData : fieldData?.bidang ?? fieldData?.data ?? []
        setUniversity(result.university)
        setFields(Array.isArray(fieldItems) ? fieldItems : [])
      })
      .catch((requestError) => {
        if (active) setError(requestError.response?.data?.message ?? 'Daftar organisasi gagal dimuat.')
      })

    return () => { active = false }
  }, [universityId])

  // Pencarian dilakukan di backend (/organisasi/cari), dengan debounce 300 ms
  useEffect(() => {
    let active = true
    setLoading(true)
    setError('')
    const timer = setTimeout(() => {
      searchOrganizations({ keyword: query, id_universitas: universityId, jenis_organisasi: type, id_bidang: field })
        .then((items) => { if (active) setOrganizations(items) })
        .catch((requestError) => {
          if (active) setError(requestError.response?.data?.message ?? 'Daftar organisasi gagal dimuat.')
        })
        .finally(() => {
          if (active) setLoading(false)
        })
    }, 300)

    return () => {
      active = false
      clearTimeout(timer)
    }
  }, [universityId, query, type, field])

  const resetFilters = () => {
    setQuery('')
    setType('')
    setField('')
  }

  return (
    <main className="organization-directory-main">
      <nav className="organization-list-breadcrumb" aria-label="Breadcrumb">
        <Link to={directoryPath}>Direktori Organisasi</Link>
        <span aria-hidden="true">&gt;</span>
        <span className="is-current" aria-current="page">{university?.label ?? (loading ? 'Memuat...' : 'Kampus')}</span>
      </nav>
      <div className="organization-directory-heading">
        <div className="organization-list-title">
          <Link className="organization-list-back" to={directoryPath} aria-label="Kembali ke direktori">
            <BackIcon />
          </Link>
          <h1>Organisasi di {university?.label ?? (loading ? 'Memuat...' : 'Kampus')}</h1>
        </div>
        <p>Pilih organisasi mahasiswa untuk melihat informasi yang tersedia.</p>
      </div>

      <section className="organization-directory-filters" aria-label="Filter organisasi">
        <label>
          <span>Cari Organisasi</span>
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Cari nama organisasi" />
        </label>
        <label>
          <span>Jenis Organisasi</span>
          <select value={type} onChange={(event) => setType(event.target.value)}>
            <option value="">Semua jenis</option>
            <option value="BEM">BEM</option>
            <option value="HMJ">HMJ</option>
            <option value="UKM">UKM</option>
          </select>
        </label>
        <label>
          <span>Bidang</span>
          <select value={field} onChange={(event) => setField(event.target.value)}>
            <option value="">Semua bidang</option>
            {fields.map((item) => <option key={item.id_bidang} value={item.id_bidang}>{item.nama_bidang}</option>)}
          </select>
        </label>
      </section>

      {loading && <div className="organization-directory-state">Memuat organisasi...</div>}
      {!loading && error && <div className="organization-directory-state organization-directory-state--error" role="alert">{error}</div>}
      {!loading && !error && organizations.length === 0 && (
        <section className="organization-directory-empty" role="status">
          <span className="organization-directory-empty__icon"><AdminIcon name="filter-alert" size={28} /></span>
          <h2>Organisasi tidak ditemukan.</h2>
          <p>Tidak ada organisasi yang cocok dengan kata kunci atau filter yang Anda pilih. Coba gunakan kata kunci lain atau setel ulang filter pencarian untuk menjelajahi unit kegiatan lainnya.</p>
          <div className="organization-directory-empty__actions">
            <button type="button" className="is-primary" onClick={resetFilters}><AdminIcon name="rotate-cw" size={16} /> Reset filter pencarian</button>
            <button type="button" className="is-secondary" onClick={resetFilters}><AdminIcon name="compass" size={16} /> Lihat Semua Organisasi {university?.label}</button>
          </div>
        </section>
      )}
      {!loading && !error && organizations.length > 0 && (
        <section className="organization-directory-grid" aria-label="Daftar organisasi">
          {organizations.map((organization) => (
            <article className="organization-directory-card" key={organization.id}>
               <div className="organization-directory-card__icon" aria-hidden="true">
                 <AdminIcon name={organization.type === 'BEM' ? 'building' : 'users'} size={22} />
               </div>
              <span className="organization-directory-card__type">{organization.type}</span>
              <span className="organization-directory-card__verified">Terverifikasi</span>
               <h2>{organization.name ?? organization.label ?? 'Organisasi'}</h2>
               <p className="organization-directory-card__field">{organization.field_name || 'Bidang belum tersedia'}</p>
              <Link className="organization-directory-card__profile-link" to={organizationPath(organization.id)}>Lihat profil <span aria-hidden="true">→</span></Link>
            </article>
          ))}
        </section>
      )}

      <section className="organization-collaboration-card" aria-label="Usulan kolaborasi">
        <div className="organization-collaboration-card__icon"><CollaborationIcon /></div>
        <div className="organization-collaboration-card__copy">
          <h2>Ingin Mengajukan Kolaborasi Antar-Kampus?</h2>
          <p>Hubungkan HMJ/UKM Anda dengan delegasi Universitas Muhammadiyah se-Jawa Timur.</p>
        </div>
        <button type="button" onClick={() => Swal.fire({ icon: 'info', title: 'Fitur belum tersedia', text: 'Usulan kolaborasi antar-kampus menunggu integrasi layanan backend.', confirmButtonColor: '#2563eb' })}>
          <CollaborationIcon /> Buat Usulan Kolaborasi
        </button>
      </section>
    </main>
  )
}

export default OrganizationListPage
