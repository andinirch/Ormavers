import { useEffect, useState } from 'react'
import Swal from 'sweetalert2'
import { getProfile } from '../api/profile.js'
import { getMyRegistrationStatus } from '../services/registrationStatusApi.js'
import { getOrganizationDescription, updateOrganizationDescription, listOrganizationContacts, createOrganizationContact, updateOrganizationContact, deleteOrganizationContact, listOrganizationPortfolios, getBidangOrganisasi } from '../api/organizationManagement.js'
import { getUniversitas } from '../services/orgApi.js'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import '../styles/organization-management.css'

const CONTACT_TYPES = { wa: 'WhatsApp', instagram: 'Instagram', email: 'Email', tiktok: 'TikTok' }

function BackIcon() {
  return <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
}

function OrganizationManagementPage() {
  const { user, roles } = useAuth()
  const roleNames = roles.map((role) => typeof role === 'string' ? role : role?.name).filter(Boolean)
  const isHmjUkm = roleNames.includes('akun_hmj_ukm') || user?.peran === 'akun_hmj_ukm'
  const [description, setDescription] = useState('')
  const [portfolioCount, setPortfolioCount] = useState(0)
  const [universityLogo, setUniversityLogo] = useState(null)
  const [bidangOptions, setBidangOptions] = useState([])
  const [profile, setProfile] = useState({ name: '', photoUrl: null })
  const [organizationName, setOrganizationName] = useState('')
  const [contacts, setContacts] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  const loadData = async () => {
    setLoading(true)
    setError('')
    try {
      const [descriptionResult, contactsResult, profileResult, statusResult] = await Promise.all([
        getOrganizationDescription(),
        listOrganizationContacts(),
        getProfile(),
        getMyRegistrationStatus(),
      ])
      setDescription(descriptionResult.data?.deskripsi ?? '')
      setContacts(contactsResult.data?.kontak ?? contactsResult.data ?? [])
      setProfile({ name: profileResult.data?.nama ?? '', photoUrl: profileResult.data?.foto_profil_url ?? null })
      setOrganizationName(statusResult.data?.organisasiDetail?.nama_organisasi ?? '')
      const detail = statusResult.data?.organisasiDetail
      const [portfolioResult, bidangResult, universitasResult] = await Promise.allSettled([
        listOrganizationPortfolios(),
        getBidangOrganisasi(),
        getUniversitas(),
      ])
      setPortfolioCount(portfolioResult.status === 'fulfilled' ? (portfolioResult.value.data ?? []).length : 0)
      setBidangOptions(bidangResult.status === 'fulfilled' ? (bidangResult.value.data ?? []) : [])
      const matchedUniversity = universitasResult.status === 'fulfilled'
        ? universitasResult.value.find((item) => item.label === detail?.nama_universitas)
        : null
      setUniversityLogo(matchedUniversity?.logo ?? null)
    } catch (requestError) {
      setError(requestError.response?.data?.message ?? 'Data profil organisasi gagal dimuat.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    queueMicrotask(() => { loadData() })
  }, [])

  const saveDescription = async () => {
    setSaving(true)
    try {
      await updateOrganizationDescription(description)
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Deskripsi organisasi berhasil diperbarui.', confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      await Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: requestError.response?.data?.message ?? 'Deskripsi gagal diperbarui.', confirmButtonColor: '#2563eb' })
    } finally { setSaving(false) }
  }

  const contactRows = Object.entries(CONTACT_TYPES).map(([jenis, label]) => ({
    jenis,
    label,
    saved: contacts.find((item) => item.jenis === jenis),
  }))

  const updateContactValue = (jenis, tautan) => {
    setContacts((current) => {
      const existing = current.find((item) => item.jenis === jenis)
      if (existing) return current.map((item) => item.jenis === jenis ? { ...item, tautan } : item)
      return [...current, { jenis, tautan }]
    })
  }

  const saveContacts = async () => {
    setSaving(true)
    try {
      const changed = contacts.filter((item) => item.tautan?.trim())
      for (const item of changed) {
        if (item.id_kontak ?? item.id) {
          await updateOrganizationContact(item.id_kontak ?? item.id, { jenis: item.jenis, tautan: item.tautan.trim() })
        } else {
          await createOrganizationContact({ jenis: item.jenis, tautan: item.tautan.trim() })
        }
      }
      const result = await listOrganizationContacts()
      setContacts(result.data?.kontak ?? result.data ?? [])
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Kontak berhasil disimpan.', confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      await Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: requestError.response?.data?.message ?? 'Kontak gagal disimpan.', confirmButtonColor: '#2563eb' })
    } finally { setSaving(false) }
  }

  const removeContact = async (jenis) => {
    const item = contacts.find((contactItem) => contactItem.jenis === jenis)
    if (!item) return
    
    // Validasi ID kontak exist sebelum delete
    if (!item.id_kontak) {
      await Swal.fire({
        icon: 'error',
        title: 'Kesalahan',
        text: 'ID kontak tidak ditemukan. Silakan refresh halaman dan coba lagi.',
        confirmButtonColor: '#2563eb'
      })
      return
    }
    
    const confirmed = await Swal.fire({ icon: 'warning', title: 'Hapus kontak?', text: 'Kontak yang dihapus tidak dapat dipulihkan.', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#dc2626' })
    if (!confirmed.isConfirmed) return
    try {
      await deleteOrganizationContact(item.id_kontak)
      setContacts((current) => current.filter((contactItem) => contactItem.jenis !== jenis))
    } catch (requestError) {
      await Swal.fire({ icon: 'error', title: 'Gagal menghapus', text: requestError.response?.data?.message ?? 'Kontak gagal dihapus.', confirmButtonColor: '#2563eb' })
    }
  }


  const completenessItems = [
    { label: 'Deskripsi organisasi', done: description.trim() !== '' },
    { label: 'Kontak organisasi', done: contacts.some((item) => item.tautan?.trim()) },
    { label: 'Portofolio', done: portfolioCount > 0 },
  ]
  const completenessPercent = Math.round((completenessItems.filter((item) => item.done).length / completenessItems.length) * 100)

  return <main className="organization-management-page">
    <header className="organization-management-heading"><a className="organization-management-back" href="/verifikasi/direktori" aria-label="Kembali ke direktori"><BackIcon /></a><div><span className="directory-eyebrow">KELOLA PROFIL ORGANISASI</span><h1>Profil Organisasi</h1><p>Kelola deskripsi, kontak, dan identitas yang tampil di Direktori Organisasi.</p></div></header>
    {loading && <div className="organization-management-state">Memuat profil organisasi...</div>}
    {!loading && error && <div className="organization-management-state organization-management-state--error">{error}</div>}
    {!loading && !error && <div className="organization-management-layout">
      <aside className="organization-management-sidebar">
        <section className="organization-management-card organization-management-summary"><div className="organization-management-avatar">{profile.name ? profile.name.slice(0, 2).toUpperCase() : 'OR'}</div><h2>{organizationName || 'Organisasi'}</h2><span>Akun Organisasi</span><label className="organization-management-upload">Ubah foto profil<input type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" /></label><small>JPG/JPEG/PNG · Maks. 2 MB</small></section>
        {!isHmjUkm && (
          <section className="organization-management-card organization-logo-card">
            <header className="organization-logo-card__header">
              <h2>Logo universitas</h2>
              <AdminIcon name="university" size={20} />
            </header>
            <div className="organization-logo-card__preview">
              {universityLogo
                ? <img className="organization-logo-card__image" src={universityLogo} alt="Logo universitas" />
                : <>
                    <span className="organization-logo-card__badge"><AdminIcon name="graduation-cap" size={28} /></span>
                    <p>Logo universitas belum tersedia</p>
                  </>}
            </div>
            <button type="button" className="organization-logo-card__upload" disabled>
              <AdminIcon name="upload-cloud" size={18} /> Ubah logo universitas
            </button>
            <small className="organization-logo-card__note">JPG atau PNG (Format transparan dianjurkan) · Menunggu integrasi backend</small>
          </section>
        )}
        <section className="organization-management-card organization-completeness-card">
          <header className="organization-completeness-card__header">
            <h2>Kelengkapan Profil</h2>
            <strong>{completenessPercent}%</strong>
          </header>
          <div className="organization-completeness-card__bar" role="progressbar" aria-valuenow={completenessPercent} aria-valuemin={0} aria-valuemax={100}><span style={{ width: `${completenessPercent}%` }} /></div>
          <ul className="organization-completeness-card__list">
            {completenessItems.map((item) => (
              <li key={item.label} className={item.done ? 'is-done' : ''}>
                <AdminIcon name={item.done ? 'check-circle' : 'info'} size={16} /> {item.label}
              </li>
            ))}
          </ul>
          <small className="organization-completeness-card__note">Indikator berdasarkan deskripsi, kontak, dan portofolio.</small>
        </section>
      </aside>
      <div className="organization-management-content">
        <section className="organization-management-card"><header><AdminIcon name="info" /><div><h2>Deskripsi organisasi</h2><p>Gambaran singkat organisasi untuk halaman profil.</p></div></header><textarea value={description} onChange={(event) => setDescription(event.target.value)} maxLength={1000} placeholder="Tuliskan deskripsi organisasi..." /><div className="organization-management-actions"><button type="button" className="organization-management-secondary" disabled><AdminIcon name="edit" size={14} /> Ubah</button><button type="button" onClick={saveDescription} disabled={saving}><AdminIcon name="save" size={15} /> Simpan perubahan</button></div></section>
        <section className="organization-management-card"><header><AdminIcon name="users" /><div><h2>Contact Person</h2><p>Tautan WhatsApp, Instagram, Email, atau Tiktok.</p></div></header><div className="organization-contact-list">{contactRows.map(({ jenis, label, saved }) => <div key={jenis}><span>{label}</span><input value={saved?.tautan ?? ''} onChange={(event) => updateContactValue(jenis, event.target.value)} placeholder="Tautan atau nomor kontak" /><button type="button" onClick={() => removeContact(jenis)} disabled={!saved || saving}>Hapus</button></div>)}</div><div className="organization-management-actions"><button type="button" className="organization-management-secondary" disabled><AdminIcon name="edit" size={14} /> Ubah</button><button type="button" onClick={saveContacts} disabled={saving}><AdminIcon name="save" size={15} /> Simpan perubahan</button></div></section>
        <section className="organization-management-card">
          <header className="organization-identity-card-header">
            <div className="organization-identity-card-title">
              <span className="organization-identity-icon-box">
                <AdminIcon name="building" size={20} />
              </span>
              <div>
                <h2>Identitas organisasi</h2>
                <p>Legalitas sekretariat dan periode aktif kepengurusan saat ini.</p>
              </div>
            </div>
            <span className="organization-identity-badge">
              <AdminIcon name="lock" size={13} /> Aktif 2026
            </span>
          </header>
          <div className="organization-identity-form">
            <label className="organization-identity-field">
              <span>Bidang organisasi</span>
              <div className="organization-identity-input-wrap">
                <select disabled defaultValue="">
                  <option value="">Pilih bidang organisasi</option>
                  {bidangOptions.map((item) => <option key={item.id_bidang} value={item.id_bidang}>{item.nama_bidang}</option>)}
                </select>
              </div>
            </label>

            <label className="organization-identity-field">
              <span>Alamat organisasi</span>
              <div className="organization-identity-input-wrap organization-identity-input-wrap--icon">
                <span className="organization-identity-field-icon" aria-hidden="true">
                  <AdminIcon name="map-pin" size={16} />
                </span>
                <input
                  placeholder="Gedung F Lantai 2, Kampus UMG, Jl. Sumatera No.101, Randuagung, Gresik"
                  disabled
                />
              </div>
            </label>

            <div className="organization-identity-dates">
              <label className="organization-identity-field">
                <span>Tanggal mulai SK Kepengurusan</span>
                <div className="organization-identity-input-wrap organization-identity-input-wrap--icon">
                  <span className="organization-identity-field-icon" aria-hidden="true">
                    <AdminIcon name="calendar" size={16} />
                  </span>
                  <input placeholder="01/01/2026" disabled />
                </div>
              </label>

              <label className="organization-identity-field">
                <span>Tanggal berakhir SK Kepengurusan</span>
                <div className="organization-identity-input-wrap organization-identity-input-wrap--icon">
                  <span className="organization-identity-field-icon" aria-hidden="true">
                    <AdminIcon name="calendar" size={16} />
                  </span>
                  <input placeholder="31/12/2026" disabled />
                </div>
              </label>
            </div>

            <div className="organization-identity-infobox">
              <span className="organization-identity-infobox__icon" aria-hidden="true">
                <AdminIcon name="checkbox-checked" size={16} />
              </span>
              <p>
                Perubahan periode kepengurusan akan memicu penyesuaian otomatis hak akses delegasi di akhir masa bakti.
              </p>
            </div>
          </div>
          <div className="organization-management-actions">
            <button type="button" className="organization-management-secondary" disabled>
              <AdminIcon name="edit" size={14} /> Ubah
            </button>
            <button type="button" disabled>
              <AdminIcon name="save" size={15} /> Simpan perubahan
            </button>
          </div>
        </section>
      </div>
    </div>}
  </main>
}

export default OrganizationManagementPage
