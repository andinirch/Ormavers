import { useEffect, useState } from 'react'
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import PageHeader from '../components/PageHeader.jsx'
import { getBidangOrganisasi } from '../api/organizationManagement.js'
import { getDirectoryOrganizations, searchOrganizations } from '../services/orgApi.js'
import '../styles/organization-directory.css'

const ORG_TYPE_LABELS = {
  BEM: 'Badan Eksekutif Mahasiswa (BEM)',
  HMJ: 'Himpunan Mahasiswa (HMJ)',
  UKM: 'Unit Kegiatan Mahasiswa (UKM)',
}

function formatOrgTypeFull(type) {
  if (!type) return 'Organisasi Mahasiswa'
  const key = String(type).trim().toUpperCase()
  return ORG_TYPE_LABELS[key] || type
}

function OrganizationLogo({ organization }) {
  const [failed, setFailed] = useState(false)
  const logoSrc = organization.logo_url || organization.logo || organization.foto_profil

  const typeAcronym = (organization.type || organization.organization_type || organization.jenis_organisasi || 'ORG').toUpperCase()

  if (!logoSrc || failed) {
    return <span className="org-list-card__fallback" aria-hidden="true">{typeAcronym}</span>
  }

  return (
    <img
      src={logoSrc}
      alt={organization.name ?? organization.label ?? 'Logo organisasi'}
      onError={() => setFailed(true)}
    />
  )
}

function BackIcon() {
  return <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
}

function CollaborationIcon() {
  return <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.07.07l2-2a5 5 0 0 0-7.07-7.07l-1.15 1.15" /><path d="M14 11a5 5 0 0 0-7.07-.07l-2 2A5 5 0 0 0 12 20l1.15-1.15" /></svg>
}

function OrganizationListPage() {
  const navigate = useNavigate()
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
    const timer = setTimeout(() => {
      setLoading(true)
      setError('')
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
      <PageHeader
        title={`Organisasi di ${university?.label ?? (loading ? 'Memuat...' : 'Kampus')}`}
        subtitle="Pilih organisasi mahasiswa untuk melihat informasi yang tersedia."
        breadcrumb={[
          { label: 'Direktori Organisasi', to: directoryPath },
          university?.label ?? (loading ? 'Memuat...' : 'Kampus')
        ]}
        icon="building"
        action={
          <Link to={directoryPath} className="page-header-back-btn">
            &larr; Kembali
          </Link>
        }
      />

      <section className="organization-directory-filters" aria-label="Filter organisasi">
        <label className="org-filter-group">
          <span className="org-filter-label">
            <AdminIcon name="search" size={14} />
            <span>Cari Organisasi</span>
          </span>
          <div className="org-filter-input-wrapper">
            <span className="org-filter-input-icon org-filter-input-icon--left" aria-hidden="true">
              <AdminIcon name="search" size={16} />
            </span>
            <input
              type="text"
              className="org-filter-input org-filter-input--with-icon"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Cari nama organisasi"
            />
          </div>
        </label>

        <label className="org-filter-group">
          <span className="org-filter-label">
            <AdminIcon name="university" size={14} />
            <span>Universitas</span>
          </span>
          <div className="org-filter-input-wrapper">
            <input
              type="text"
              className="org-filter-input org-filter-input--locked"
              value={university?.label ?? (loading ? 'Memuat...' : '')}
              readOnly
              disabled
              title={university?.label ?? ''}
            />
            <span className="org-filter-input-icon org-filter-input-icon--right" aria-hidden="true">
              <AdminIcon name="lock" size={16} />
            </span>
          </div>
        </label>

        <label className="org-filter-group">
          <span className="org-filter-label">
            <AdminIcon name="sliders" size={14} />
            <span>Jenis Organisasi</span>
          </span>
          <div className="org-filter-input-wrapper">
            <select
              className="org-filter-select"
              value={type}
              onChange={(event) => setType(event.target.value)}
            >
              <option value="">Semua jenis</option>
              <option value="BEM">BEM</option>
              <option value="HMJ">HMJ</option>
              <option value="UKM">UKM</option>
            </select>
          </div>
        </label>

        <label className="org-filter-group">
          <span className="org-filter-label">
            <AdminIcon name="grid" size={14} />
            <span>Bidang</span>
          </span>
          <div className="org-filter-input-wrapper">
            <select
              className="org-filter-select"
              value={field}
              onChange={(event) => setField(event.target.value)}
            >
              <option value="">Semua bidang</option>
              {fields.map((item) => (
                <option key={item.id_bidang} value={item.id_bidang}>
                  {item.nama_bidang}
                </option>
              ))}
            </select>
          </div>
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
            <article className="org-list-card organization-directory-card" key={organization.id}>
              <div className="org-list-card__badge" aria-hidden="true">
                <OrganizationLogo organization={organization} />
              </div>
              <div className="org-list-card__content">
                <span className="org-list-card__verified">Terverifikasi</span>
                <h2 className="org-list-card__name">{organization.name ?? organization.label ?? 'Organisasi'}</h2>
                <p className="org-list-card__field">
                  <AdminIcon name={(organization.type || organization.organization_type) === 'BEM' ? 'landmark' : 'user-group'} size={15} />
                  <span>{formatOrgTypeFull(organization.type || organization.organization_type)}</span>
                </p>
              </div>
              <Link className="org-list-card__btn" to={organizationPath(organization.id)}>
                Lihat profil <span aria-hidden="true">→</span>
              </Link>
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
        <button type="button" onClick={() => navigate('/verifikasi/kelola-event/baru')}>
          <CollaborationIcon /> Buat Usulan Kolaborasi
        </button>
      </section>
    </main>
  )
}

export default OrganizationListPage
