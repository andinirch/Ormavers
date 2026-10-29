import { useEffect, useState } from 'react'
import Swal from 'sweetalert2'
import { assetUrl } from '../api/api.js'
import { clearCache } from '../services/cacheService.js'
import { getProfile } from '../api/profile.js'
import { getMyRegistrationStatus } from '../services/registrationStatusApi.js'
import { getOrganizationIdentity, updateOrganizationIdentity, getBidangOrganisasi, getOrganizationDescription, updateOrganizationDescription, listOrganizationContacts, createOrganizationContact, updateOrganizationContact, deleteOrganizationContact, listOrganizationPortfolios } from '../api/organizationManagement.js'
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
  const [identity, setIdentity] = useState({ id_bidang: '', alamat: '', tanggal_mulai_sk: '', tanggal_berakhir_sk: '', logo: null, logo_universitas: null })
  const [identityDraft, setIdentityDraft] = useState(null)
  const [identityLogoFile, setIdentityLogoFile] = useState(null)
  const [universityLogoFile, setUniversityLogoFile] = useState(null)
  const [universityLogoPreview, setUniversityLogoPreview] = useState(null)
  const [bidangOptions, setBidangOptions] = useState([])
  const [bidangError, setBidangError] = useState('')
  const [editingDescription, setEditingDescription] = useState(false)
  const [editingContacts, setEditingContacts] = useState(false)
  const [editingIdentity, setEditingIdentity] = useState(false)
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
      const [descriptionResult, contactsResult, profileResult, statusResult, identityResult] = await Promise.all([
        getOrganizationDescription(),
        listOrganizationContacts(),
        getProfile(),
        getMyRegistrationStatus(),
        getOrganizationIdentity(),
      ])
      const identityData = identityResult.data ?? {}
      setDescription(descriptionResult.data?.deskripsi ?? '')
      setContacts(contactsResult.data?.kontak ?? contactsResult.data ?? [])
      const normalizedIdentity = {
        ...identityData,
        logo: assetUrl(identityData.logo),
        logo_universitas: assetUrl(identityData.logo_universitas),
      }
      setIdentity(normalizedIdentity)
      setIdentityDraft(normalizedIdentity)
      setUniversityLogo(normalizedIdentity.logo_universitas ?? null)
      setUniversityLogoPreview(null)
      setProfile({ name: profileResult.data?.nama ?? '', photoUrl: assetUrl(profileResult.data?.foto_profil_url) })
      setOrganizationName(statusResult.data?.organisasiDetail?.nama_organisasi ?? '')
      const detail = statusResult.data?.organisasiDetail
      const [portfolioResult, universitasResult, bidangResult] = await Promise.allSettled([
        listOrganizationPortfolios(),
        getUniversitas(),
        getBidangOrganisasi(),
      ])
      const portfolioData = portfolioResult.status === 'fulfilled' ? portfolioResult.value.data : null
      const portfolioItems = Array.isArray(portfolioData) ? portfolioData : portfolioData?.portofolio ?? []
      setPortfolioCount(portfolioItems.length)
      if (bidangResult.status === 'fulfilled') {
        const bidangResponse = bidangResult.value
        const bidangItems = Array.isArray(bidangResponse?.data)
          ? bidangResponse.data
          : bidangResponse?.data?.bidang ?? bidangResponse?.data?.data ?? []
        setBidangOptions(Array.isArray(bidangItems) ? bidangItems : [])
        setBidangError(Array.isArray(bidangItems) && bidangItems.length ? '' : 'Belum ada data bidang organisasi.')
      } else {
        setBidangOptions([])
        setBidangError('Daftar bidang organisasi gagal dimuat.')
      }
       // Logo universitas berasal dari endpoint identitas; jangan menimpanya dengan data master.
       if (!normalizedIdentity.logo_universitas) {
         const matchedUniversity = universitasResult.status === 'fulfilled'
           ? universitasResult.value.find((item) => item.label === detail?.nama_universitas)
           : null
         setUniversityLogo(matchedUniversity?.logo ?? null)
       }
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


  const handleProfilePhotoChange = async (event) => {
    const input = event.target
    const file = input.files?.[0]
    if (!file) return

    if (!['image/jpeg', 'image/png'].includes(file.type)) {
      await Swal.fire({ icon: 'warning', title: 'Format tidak didukung', text: 'Foto harus berformat JPG, JPEG, atau PNG.', confirmButtonColor: '#2563eb' })
      input.value = ''
      return
    }
    if (file.size > 2 * 1024 * 1024) {
      await Swal.fire({ icon: 'warning', title: 'Ukuran terlalu besar', text: 'Ukuran foto maksimal 2 MB.', confirmButtonColor: '#2563eb' })
      input.value = ''
      return
    }

    if (!identityDraft?.id_bidang || !identityDraft?.alamat || !identityDraft?.tanggal_mulai_sk || !identityDraft?.tanggal_berakhir_sk) {
      await Swal.fire({ icon: 'warning', title: 'Lengkapi identitas organisasi', text: 'Bidang, alamat, dan periode SK wajib diisi sebelum foto dijadikan logo organisasi.', confirmButtonColor: '#2563eb' })
      input.value = ''
      return
    }

    setSaving(true)
    try {
      const identityPayload = new FormData()
      identityPayload.append('id_bidang', identityDraft.id_bidang)
      identityPayload.append('alamat', identityDraft.alamat)
      identityPayload.append('tanggal_mulai_sk', identityDraft.tanggal_mulai_sk)
      identityPayload.append('tanggal_berakhir_sk', identityDraft.tanggal_berakhir_sk)
      identityPayload.append('logo', file)
      if (universityLogoFile && !isHmjUkm) identityPayload.append('logo_universitas', universityLogoFile)
      const identityResult = await updateOrganizationIdentity(identityPayload)
      const nextIdentity = identityResult.data ?? identityResult
      const normalizedIdentity = {
        ...nextIdentity,
        logo: assetUrl(nextIdentity.logo),
        logo_universitas: assetUrl(nextIdentity.logo_universitas),
      }
      setIdentity(normalizedIdentity)
      setIdentityDraft(normalizedIdentity)
      clearCache('directory_universities')
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Logo organisasi berhasil diubah.', confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      const validationErrors = requestError.response?.data?.errors
      const detail = validationErrors ? Object.values(validationErrors).flat().join('\\n') : requestError.response?.data?.message
      await Swal.fire({ icon: 'error', title: 'Gagal mengunggah foto', text: detail ?? 'Foto profil gagal diubah.', confirmButtonColor: '#2563eb' })
    } finally {
      input.value = ''
      setSaving(false)
    }
  }

  const saveIdentity = async () => {
    if (!identityDraft?.id_bidang || !identityDraft?.alamat || !identityDraft?.tanggal_mulai_sk || !identityDraft?.tanggal_berakhir_sk) {
      await Swal.fire({ icon: 'warning', title: 'Data belum lengkap', text: 'Bidang, alamat, dan periode SK wajib diisi.', confirmButtonColor: '#2563eb' })
      return
    }
    setSaving(true)
    try {
      const payload = new FormData()
      payload.append('id_bidang', identityDraft.id_bidang ?? '')
      payload.append('alamat', identityDraft.alamat ?? '')
      payload.append('tanggal_mulai_sk', identityDraft.tanggal_mulai_sk ?? '')
      payload.append('tanggal_berakhir_sk', identityDraft.tanggal_berakhir_sk ?? '')
      if (identityLogoFile) payload.append('logo', identityLogoFile)
      if (universityLogoFile && !isHmjUkm) payload.append('logo_universitas', universityLogoFile)
      const result = await updateOrganizationIdentity(payload)
      const responseIdentity = result.data ?? result
      const nextIdentity = {
        ...responseIdentity,
        logo: assetUrl(responseIdentity.logo),
        logo_universitas: assetUrl(responseIdentity.logo_universitas),
      }
      setIdentity(nextIdentity)
      setIdentityDraft(nextIdentity)
      setUniversityLogo(nextIdentity.logo_universitas || universityLogo)
      clearCache('directory_universities')
      setIdentityLogoFile(null)
      setUniversityLogoFile(null)
      setEditingIdentity(false)
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Identitas organisasi berhasil disimpan.', confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      const validationErrors = requestError.response?.data?.errors
      const detail = validationErrors
        ? Object.values(validationErrors).flat().join('\n')
        : requestError.response?.data?.message
      await Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: detail ?? 'Identitas organisasi gagal disimpan.', confirmButtonColor: '#2563eb' })
    } finally { setSaving(false) }
  }

  const completenessItems = [
    { label: 'Logo & Lambang Valid', done: Boolean(identity.logo && (isHmjUkm || identity.logo_universitas)) },
    { label: 'SK Kepengurusan Masih & Terdata', done: Boolean(identity.tanggal_mulai_sk && identity.tanggal_berakhir_sk) },
    { label: `${contacts.filter((item) => item.tautan?.trim()).length} Saluran Kontak Aktif Terverifikasi`, done: contacts.filter((item) => item.tautan?.trim()).length > 0 },
  ]
  const completenessPercent = Math.round((completenessItems.filter((item) => item.done).length / completenessItems.length) * 100)

  return <main className="organization-management-page">
    <header className="organization-management-heading"><a className="organization-management-back" href="/verifikasi/direktori" aria-label="Kembali ke direktori"><BackIcon /></a><div><span className="directory-eyebrow">KELOLA PROFIL ORGANISASI</span><h1>Profil Organisasi</h1><p>Kelola deskripsi, kontak, dan identitas yang tampil di Direktori Organisasi.</p></div></header>
    {loading && <div className="organization-management-state">Memuat profil organisasi...</div>}
    {!loading && error && <div className="organization-management-state organization-management-state--error">{error}</div>}
    {!loading && !error && <div className="organization-management-layout">
      <aside className="organization-management-sidebar">
        <section className="organization-management-card organization-management-summary"><div className="organization-management-avatar">{identity.logo ? <img src={identity.logo} alt="Logo organisasi" /> : (organizationName ? organizationName.slice(0, 2).toUpperCase() : 'OR')}</div><h2>{organizationName || 'Organisasi'}</h2><span>Akun Organisasi</span><label className="organization-management-upload">Ubah logo organisasi<input type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" onChange={handleProfilePhotoChange} disabled={saving} /></label><small>JPG/JPEG/PNG · Maks. 2 MB</small></section>
        {!isHmjUkm && (
          <section className="organization-management-card organization-logo-card">
            <header className="organization-logo-card__header">
              <h2>Logo universitas</h2>
              <AdminIcon name="university" size={20} />
            </header>
            <div className="organization-logo-card__preview">
               {universityLogoPreview || universityLogo
                 ? <img className="organization-logo-card__image" src={universityLogoPreview || universityLogo} alt="Logo universitas" />
                : <>
                    <span className="organization-logo-card__badge"><AdminIcon name="graduation-cap" size={28} /></span>
                    <p>Logo universitas belum tersedia</p>
                  </>}
            </div>
             {!isHmjUkm && (
               <label className="organization-logo-card__upload">
                 <AdminIcon name="upload-cloud" size={18} /> Ubah logo universitas
                 <input className="profile-photo-input" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" disabled={saving} onClick={() => setEditingIdentity(true)}
                 onChange={(event) => {
                   const file = event.target.files?.[0] ?? null
                   setUniversityLogoFile(file)
                   if (file) setUniversityLogoPreview(URL.createObjectURL(file))
                 }} />
               </label>
             )}
             <small className="organization-logo-card__note">JPG atau PNG (Format transparan dianjurkan)</small>
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
        <section className="organization-management-card"><header><AdminIcon name="info" /><div><h2>Deskripsi organisasi</h2><p>Gambaran singkat organisasi untuk halaman profil.</p></div></header><textarea value={description} onChange={(event) => setDescription(event.target.value)} maxLength={1000} placeholder="Tuliskan deskripsi organisasi..." disabled={!editingDescription || saving} /><div className="organization-management-actions"><button type="button" className="organization-management-secondary" onClick={() => setEditingDescription(true)} disabled={saving}> <AdminIcon name="edit" size={14} /> Ubah</button><button type="button" onClick={saveDescription} disabled={!editingDescription || saving}><AdminIcon name="save" size={15} /> Simpan perubahan</button></div></section>
        <section className="organization-management-card"><header><AdminIcon name="users" /><div><h2>Contact Person</h2><p>Tautan WhatsApp, Instagram, Email, atau Tiktok.</p></div></header><div className="organization-contact-list">{contactRows.map(({ jenis, label, saved }) => <div key={jenis}><span>{label}</span><input value={saved?.tautan ?? ''} onChange={(event) => updateContactValue(jenis, event.target.value)} placeholder="Tautan" disabled={!editingContacts || saving} /><button type="button" onClick={() => removeContact(jenis)} disabled={!saved || saving || !editingContacts}>Hapus</button></div>)}</div><div className="organization-management-actions"><button type="button" className="organization-management-secondary" onClick={() => setEditingContacts(true)} disabled={saving}><AdminIcon name="edit" size={14} /> Ubah</button><button type="button" onClick={saveContacts} disabled={!editingContacts || saving}><AdminIcon name="save" size={15} /> Simpan perubahan</button></div></section>
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
<select value={identityDraft?.id_bidang ?? ''} onChange={(event) => setIdentityDraft((current) => ({ ...current, id_bidang: event.target.value }))} disabled={!editingIdentity || saving}>
                   <option value="">Pilih bidang organisasi</option>
                   {bidangOptions.map((item) => <option key={item.id_bidang} value={item.id_bidang}>{item.nama_bidang}</option>)}
                 </select>
               </div>
               {bidangError && <small className="organization-identity-error">{bidangError}</small>}
              </label>

              <label className="organization-identity-field">
              <span>Alamat organisasi</span>
              <div className="organization-identity-input-wrap organization-identity-input-wrap--icon">
                <span className="organization-identity-field-icon" aria-hidden="true">
                  <AdminIcon name="map-pin" size={16} />
                </span>
                <input
value={identityDraft?.alamat ?? ''}
                   onChange={(event) => setIdentityDraft((current) => ({ ...current, alamat: event.target.value }))}
                   placeholder="Alamat sekretariat organisasi"
                   disabled={!editingIdentity || saving}
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
                  <input type="date" value={identityDraft?.tanggal_mulai_sk ?? ''} onChange={(event) => setIdentityDraft((current) => ({ ...current, tanggal_mulai_sk: event.target.value }))} disabled={!editingIdentity || saving} />
                </div>
              </label>

              <label className="organization-identity-field">
                <span>Tanggal berakhir SK Kepengurusan</span>
                <div className="organization-identity-input-wrap organization-identity-input-wrap--icon">
                  <span className="organization-identity-field-icon" aria-hidden="true">
                    <AdminIcon name="calendar" size={16} />
                  </span>
                  <input type="date" value={identityDraft?.tanggal_berakhir_sk ?? ''} onChange={(event) => setIdentityDraft((current) => ({ ...current, tanggal_berakhir_sk: event.target.value }))} disabled={!editingIdentity || saving} />
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
            <button type="button" className="organization-management-secondary" onClick={() => { setIdentityDraft(identity); setEditingIdentity(true) }} disabled={saving}>
              <AdminIcon name="edit" size={14} /> Ubah
            </button>
            <button type="button" onClick={saveIdentity} disabled={!editingIdentity || saving}>
              <AdminIcon name="save" size={15} /> Simpan perubahan
            </button>
          </div>
        </section>
      </div>
    </div>}
  </main>
}

export default OrganizationManagementPage
