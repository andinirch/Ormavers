import { useEffect, useState } from 'react'
import { useLocation, useParams } from 'react-router-dom'
import Swal from 'sweetalert2'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import ImageWithFallback from '../components/ImageWithFallback.jsx'
import { getOrganizationDetail } from '../services/orgApi.js'
import '../styles/organization-directory.css'

const CONTACT_LINKS = [
  { key: 'wa', label: 'WhatsApp', icon: 'message' },
  { key: 'instagram', label: 'Instagram', icon: 'camera' },
  { key: 'email', label: 'Email', icon: 'mail' },
  { key: 'tiktok', label: 'Tiktok', icon: 'music-tiktok' },
]

const ORGANIZATION_PREFIXES = ['HMJ', 'UKM', 'BEM']

function getInitials(name = '') {
  const words = name.split(/\s+/).filter(Boolean)
  const meaningful = words.length > 1 && ORGANIZATION_PREFIXES.includes(words[0].toUpperCase()) ? words.slice(1) : words
  return meaningful.slice(0, 2).map((word) => word[0].toUpperCase()).join('')
}

function BackIcon() {
  return <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
}

function normalizeContactUrl(url) {
  if (!url) return ''
  if (/^(https?:|mailto:|tel:)/i.test(url)) return url
  if (/^\+?\d[\d\s-]+$/.test(url)) return `https://wa.me/${url.replace(/\D/g, '')}`
  return `https://${url}`
}

function groupPortfolioByYear(items = []) {
  return items.reduce((groups, item) => {
    const year = item.tahun ?? 'Lainnya'
    const group = groups.find((entry) => String(entry.year) === String(year))
    if (group) group.items.push(item)
    else groups.push({ year, items: [item] })
    return groups
  }, []).sort((a, b) => Number(b.year) - Number(a.year))
}

function OrganizationDetailPage() {
  const openContact = (contact) => {
    const url = normalizeContactUrl(contact?.tautan)
    if (url) window.open(url, '_blank', 'noopener,noreferrer')
    else Swal.fire({ icon: 'info', title: 'Kontak belum tersedia', text: 'Tautan kontak ini belum diisi oleh organisasi.', confirmButtonColor: '#2563eb' })
  }
  const { pathname } = useLocation()
  const { universityId, organizationId } = useParams()
  const [university, setUniversity] = useState(null)
  const [organization, setOrganization] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const portfolioGroups = groupPortfolioByYear(organization?.portfolio)
  const directoryPath = pathname.startsWith('/superadmin/')
    ? `/superadmin/direktori/universitas/${universityId}`
    : pathname.startsWith('/admin/')
      ? `/admin/direktori/universitas/${universityId}`
      : pathname.startsWith('/verifikasi/')
        ? `/verifikasi/direktori/universitas/${universityId}`
        : `/direktori/universitas/${universityId}`

  useEffect(() => {
    let active = true
    getOrganizationDetail(organizationId)
      .then((result) => {
        if (!active) return
        setUniversity(result?.university ?? null)
        setOrganization(result ?? null)
      })
      .catch((requestError) => {
        if (active) setError(requestError.response?.data?.message ?? 'Profil organisasi gagal dimuat.')
      })
      .finally(() => {
        if (active) setLoading(false)
      })
    return () => { active = false }
  }, [organizationId, universityId])

  return (
    <main className="organization-detail-main">
      <div className="organization-directory-breadcrumb">Direktori Organisasi <span>/</span> {university?.label ?? 'Kampus'} <span>/</span> {organization?.name ?? 'Organisasi'}</div>
      <div className="organization-detail-heading">
        <a className="organization-directory-back" href={directoryPath} aria-label="Kembali ke daftar organisasi"><BackIcon /></a>
        <div className="organization-detail-profile">
          <span className="organization-detail-avatar" aria-hidden="true">
            {organization?.logo
              ? <img src={organization.logo} alt="" />
              : (getInitials(organization?.name) || 'OR')}
          </span>
          <div className="organization-detail-heading__copy">
            <h1>{organization?.name ?? 'Profil Organisasi'}</h1>
            <div className="organization-detail-tags">
              <span className="organization-detail-tag"><AdminIcon name="building" size={14} />{university?.label ?? 'Kampus belum tersedia'}</span>
              <span className="organization-detail-tag">{organization?.type ?? 'Jenis belum tersedia'}</span>
            </div>
          </div>
        </div>
      </div>

      {loading && <div className="organization-directory-state">Memuat profil organisasi...</div>}
      {!loading && error && <div className="organization-directory-state organization-directory-state--error" role="alert">{error}</div>}
      {!loading && !error && !organization && <div className="organization-directory-state">Profil organisasi tidak ditemukan.</div>}
      {!loading && !error && organization && (
        <div className="organization-detail-layout">
          <div className="organization-detail-main-col">
            <section className="organization-detail-card">
              <header className="organization-detail-card__head">
                <AdminIcon name="info" size={18} />
                <h2>Deskripsi</h2>
              </header>
              {organization.deskripsi ? (
                <p>{organization.deskripsi}</p>
              ) : (
                <p className="organization-detail-empty">Deskripsi organisasi belum tersedia.</p>
              )}
            </section>
            <section className="organization-detail-card">
              <header className="organization-detail-card__head">
                <AdminIcon name="folder-special" size={18} />
                <h2>Portofolio</h2>
              </header>
              {portfolioGroups.length ? (
                <div className="organization-detail-portfolio-list">
                  {portfolioGroups.map((group) => (
                    <div className="organization-detail-portfolio-year" key={group.year}>
                      <h3><AdminIcon name="calendar" size={16} /> {group.year}</h3>
                      {group.items.map((item) => (
                        <article className="organization-detail-portfolio-item" key={item.id_portofolio}>
                          <div className="organization-detail-portfolio-thumb">
                            <ImageWithFallback
                              src={item.dokumentasi}
                              alt={item.judul}
                              fallback={<AdminIcon name="image" size={22} />}
                            />
                          </div>
                          <div className="organization-detail-portfolio-copy">
                            <h4>{item.judul}</h4>
                            <p>{item.deskripsi}</p>
                          </div>
                        </article>
                      ))}
                    </div>
                  ))}
                </div>
              ) : (
                <p className="organization-detail-empty">Belum ada portofolio yang tersedia.</p>
              )}
            </section>
          </div>
          <aside className="organization-detail-side-col">
            <section className="organization-detail-card organization-detail-contact">
              <header className="organization-detail-card__head">
                <AdminIcon name="contact-page" size={18} />
                <div>
                  <h2>Contact Person</h2>
                  <p>Hubungi organisasi melalui tautan berikut.</p>
                </div>
              </header>
              <div className="organization-detail-contact__list">
                {CONTACT_LINKS.map((item) => {
                  const contact = organization.contacts?.find((entry) => entry.jenis === item.key)
                  return (
                    <button type="button" className="organization-detail-contact__link" key={item.key} onClick={() => openContact(contact)}>
                      <AdminIcon name={item.icon} size={16} /> {item.label}
                    </button>
                  )
                })}
              </div>
            </section>
          </aside>
        </div>
      )}
    </main>
  )
}

export default OrganizationDetailPage
