import { useEffect, useState } from 'react'
import { useAuth } from '../context/AuthContext.jsx'
import { getProfile, updatePassword, updateProfile, uploadProfilePhoto } from '../api/profile.js'
import { getMyRegistrationStatus } from '../services/registrationStatusApi.js'
import Swal from 'sweetalert2'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import '../styles/profile.css'

function resolveAssetUrl(url) {
  if (!url) return null
  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  const apiOrigin = new URL(apiUrl).origin

  try {
    const assetUrl = new URL(url, apiOrigin)
    if (assetUrl.pathname.startsWith('/storage/')) {
      return `${apiOrigin}${assetUrl.pathname}${assetUrl.search}${assetUrl.hash}`
    }
    return assetUrl.toString()
  } catch {
    return `${apiOrigin}${url.startsWith('/') ? url : `/${url}`}`
  }
}

function roleKey(user, roles) {
  const roleNames = roles?.map((role) => typeof role === 'string' ? role : role?.name).filter(Boolean) ?? []
  if (roleNames.includes('akun_hmj_ukm')) return 'hmj'
  if (roleNames.includes('akun_bem')) return 'bem'
  if (user?.peran === 'anggota' || user?.peran === 'member' || roleNames.includes('anggota') || roleNames.includes('member')) return 'anggota'
  if (user?.peran === 'akun_hmj_ukm') return 'hmj'
  return 'bem'
}

function getInitials(name) {
  return (name ?? '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}


function Field({ label, value, name, onChange, readOnly = false, type = 'text', icon, placeholder }) {
  return (
    <label className="profile-field">
      <span>{label}</span>
      <span className="profile-field__control">
        <input type={type} name={name} value={value ?? ''} onChange={onChange} readOnly={readOnly} placeholder={placeholder} />
        {icon}
      </span>
    </label>
  )
}

function PasswordField({ label, name, value, visible, placeholder, onChange, onToggle }) {
  return <Field label={label} name={name} value={value} type={visible ? 'text' : 'password'} placeholder={placeholder} onChange={onChange} icon={<button className="profile-field__toggle" type="button" onClick={onToggle} aria-label={visible ? `Sembunyikan ${label}` : `Tampilkan ${label}`}><EyeIcon visible={visible} /></button>} />
}

function EyeIcon({ visible }) {
  return <svg className="profile-field__icon" viewBox="0 0 24 24" aria-hidden="true">{visible ? <><path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5-9.5-5Z" /><circle cx="12" cy="12" r="2.5" /></> : <><path d="m3 3 18 18M10.6 6.3A10.6 10.6 0 0 1 12 6c6.1 0 9.5 6 9.5 6a16.7 16.7 0 0 1-3.1 3.6M6.2 6.3C3.8 8 2.5 12 2.5 12s3.4 6 9.5 6c1.1 0 2.1-.2 3-.5" /></>}</svg>
}

function CameraIcon() {
  return <svg className="profile-button__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7.5h3l1.5-2h5L16 7.5h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z" /><circle cx="12" cy="13.5" r="3.25" /></svg>
}

function LockIcon() {
  return <svg className="profile-button__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
}

function OrganizationIcon() {
  return <svg className="profile-organization__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16M6 20V6h12v14M9 6V3h6v3M9 10h2M13 10h2M9 14h2M13 14h2" /></svg>
}

function MemberOrganizationIcon({ type }) {
  return type === 'bem'
    ? <svg className="profile-member-org__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16M6 20V6h12v14M9 6V3h6v3M9 10h2M13 10h2M9 14h2M13 14h2" /></svg>
    : <svg className="profile-member-org__icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3" /><path d="M3 20a6 6 0 0 1 12 0M18 9v6M15 12h6" /></svg>
}

function OrganizationProfilePage() {
  const { user, roles } = useAuth()
  const key = roleKey(user, roles)
  const roleName = key === 'anggota' ? 'Anggota' : key === 'hmj' ? 'Akun Organisasi HMJ/UKM' : 'Akun Organisasi BEM'
  const personalTitle = key === 'anggota' ? 'Data diri anggota' : 'Data penanggung jawab organisasi'
  const [personal, setPersonal] = useState({ name: '', nim: '', whatsapp: '', username: '' })
  const [photoUrl, setPhotoUrl] = useState(null)
  const [organizationDetail, setOrganizationDetail] = useState(null)
  const [registrationStatus, setRegistrationStatus] = useState(null)
  const [organizations, setOrganizations] = useState([])
  const [password, setPassword] = useState({ old: '', next: '', confirm: '' })
  const [passwordVisibility, setPasswordVisibility] = useState({ old: false, next: false, confirm: false })
  const [message, setMessage] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const updatePersonal = (event) => setPersonal((current) => ({ ...current, [event.target.name]: event.target.value }))
  const savePersonal = async (event) => {
    event.preventDefault()
    setSaving(true)
    setMessage(null)
    try {
      const result = await updateProfile({ nama: personal.name, nomor_whatsapp: personal.whatsapp || null })
      const data = result?.data ?? result
      setPersonal((current) => ({ ...current, name: data?.nama ?? current.name, whatsapp: data?.nomor_whatsapp ?? current.whatsapp }))
       await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data profil berhasil diperbarui.', confirmButtonColor: '#2563eb' })
    } catch (error) {
       await Swal.fire({ icon: 'error', title: 'Gagal menyimpan', text: error.response?.data?.message ?? 'Data profil gagal diperbarui.', confirmButtonColor: '#2563eb' })
    } finally {
      setSaving(false)
    }
  }
  const savePassword = async (event) => {
    event.preventDefault()
    if (password.next.length < 8) {
      await Swal.fire({ icon: 'warning', title: 'Password belum valid', text: 'Password baru minimal 8 karakter.', confirmButtonColor: '#2563eb' })
      return
    }
    if (password.next !== password.confirm) {
      await Swal.fire({ icon: 'warning', title: 'Konfirmasi tidak cocok', text: 'Konfirmasi password tidak sama dengan password baru.', confirmButtonColor: '#2563eb' })
      return
    }
    setSaving(true)
    setMessage(null)
    try {
      await updatePassword({ password_lama: password.old, password_baru: password.next, konfirmasi_password: password.confirm })
      setPassword({ old: '', next: '', confirm: '' })
       await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Password berhasil diubah.', confirmButtonColor: '#2563eb' })
    } catch (error) {
       await Swal.fire({ icon: 'error', title: 'Gagal mengubah password', text: error.response?.data?.message ?? error.response?.data?.errors?.password_lama?.[0] ?? 'Password gagal diubah.', confirmButtonColor: '#2563eb' })
    } finally {
      setSaving(false)
    }
  }

  const handlePhotoChange = async (event) => {
    const input = event.target
    const file = input.files?.[0]
    if (!file) return

    const acceptedTypes = ['image/jpeg', 'image/png']
    const maxBytes = 2 * 1024 * 1024
    if (!acceptedTypes.includes(file.type)) {
      await Swal.fire({ icon: 'warning', title: 'Format tidak didukung', text: 'Foto harus berformat JPG, JPEG, atau PNG.', confirmButtonColor: '#2563eb' })
      input.value = ''
      return
    }
    if (file.size > maxBytes) {
      await Swal.fire({ icon: 'warning', title: 'Ukuran terlalu besar', text: 'Ukuran foto maksimal 2 MB.', confirmButtonColor: '#2563eb' })
      input.value = ''
      return
    }

    setSaving(true)
    try {
      await uploadProfilePhoto(file)
      if (photoUrl?.startsWith('blob:')) URL.revokeObjectURL(photoUrl)
      setPhotoUrl(URL.createObjectURL(file))
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Foto profil berhasil diubah.', confirmButtonColor: '#2563eb' })
    } catch (error) {
      await Swal.fire({ icon: 'error', title: 'Gagal mengunggah foto', text: error.response?.data?.message ?? error.response?.data?.errors?.foto?.[0] ?? 'Foto profil gagal diubah.', confirmButtonColor: '#2563eb' })
    } finally {
      input.value = ''
      setSaving(false)
    }
  }
  useEffect(() => {
    let active = true
    Promise.all([getProfile(), getMyRegistrationStatus()]).then(([result, statusResult]) => {
      if (!active) return
      const data = result?.data ?? result
       setPersonal((current) => ({ ...current, name: data?.nama ?? current.name, nim: data?.nim ?? '', whatsapp: data?.nomor_whatsapp ?? current.whatsapp, username: data?.username ?? current.username }))
       setPhotoUrl(resolveAssetUrl(data?.foto_profil_url))
       if (Array.isArray(data?.organisasi_diikuti)) setOrganizations(data.organisasi_diikuti)
       if (statusResult?.data?.organisasiDetail) setOrganizationDetail(statusResult.data.organisasiDetail)
       setRegistrationStatus(statusResult?.data?.status ?? null)
    }).catch(() => {
      if (active) setMessage('Data profil gagal dimuat. Menggunakan data tampilan sementara.')
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])

  const displayName = personal.name || organizationDetail?.nama_organisasi || 'Profil Saya'
  const initials = getInitials(displayName) || 'P'
  const organization = key === 'anggota' ? {
    name: organizationDetail?.nama_organisasi ?? null,
    campus: null,
    type: organizationDetail?.jenis_organisasi?.toUpperCase() ?? null,
  } : {
    name: organizationDetail?.nama_organisasi ?? null,
    campus: organizationDetail?.nama_universitas ?? null,
    parent: organizationDetail?.induk?.nama_organisasi ?? null,
    type: organizationDetail?.jenis_organisasi?.toUpperCase() ?? null,
    phone: organizationDetail?.nomor_telepon ?? null,
  }
  const organizationLabels = { name: 'Nama organisasi', campus: 'Kampus', parent: 'BEM induk', type: 'Jenis', phone: 'No. telepon' }
  const bemOrganizationLabels = { name: 'Nama organisasi', campus: 'Kampus', phone: 'No. telepon' }

  const content = (
    <main className="admin-page profile-page">
      <div className="profile-page__heading"><h1>Profil Saya</h1><p>Kelola data diri, password, dan foto profil</p></div>
      {loading && <p className="profile-message" role="status">Memuat data profil...</p>}
      <div className="profile-layout">
        <div className="profile-side">
          <section className="profile-card profile-summary"><div className="profile-avatar">{photoUrl ? <img src={photoUrl} alt="Foto profil" onError={() => setPhotoUrl(null)} /> : initials}</div><h2>{displayName}</h2><span className={`profile-role${key === 'anggota' ? ' profile-role--member' : ''}`}>{key === 'anggota' && <span className="profile-role__dot" aria-hidden="true" />}{roleName}</span><label className="profile-button profile-button--outline"><CameraIcon /> Ubah foto profil<input className="profile-photo-input" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" onChange={handlePhotoChange} disabled={saving} /></label><small>JPG/JPEG/PNG · Maks. 2 MB</small></section>
          <section className={`profile-card profile-organization${key === 'anggota' ? ' profile-organization--member' : ''}`}><header className="profile-organization__heading"><h2><OrganizationIcon /> Organisasi</h2></header>{key === 'anggota' ? (organizations.length > 0 ? <div className="profile-member-org-list">{organizations.map((item) => <div className="profile-member-org" key={item.id_organisasi ?? item.id}><MemberOrganizationIcon type={item.jenis_organisasi === 'bem' ? 'bem' : 'hmj'} /><div><small>{item.jenis_organisasi?.toUpperCase() ?? 'Organisasi'}</small><strong>{item.nama_organisasi}</strong></div></div>)}</div> : organizationDetail ? <div className="profile-member-org-list"><div className="profile-member-org"><MemberOrganizationIcon type={organizationDetail.jenis_organisasi === 'bem' ? 'bem' : 'hmj'} /><div><small>{organizationDetail.jenis_organisasi?.toUpperCase() ?? 'Organisasi'}</small><strong>{organizationDetail.nama_organisasi}</strong><small className="profile-membership-status">{registrationStatus === 'disetujui' ? 'Aktif' : registrationStatus === 'ditolak' ? 'Ditolak' : 'Menunggu verifikasi'}</small></div></div></div> : <p className="profile-empty-state">Data organisasi yang diikuti belum tersedia.</p>) : Object.entries(organization).map(([label, value]) => <div className="profile-detail" key={label}><small>{key === 'bem' ? bemOrganizationLabels[label] : key === 'hmj' ? organizationLabels[label] : label === 'parent' ? 'BEM induk' : label}</small><strong>{value}</strong></div>)}</section>

        </div>
        <div className="profile-main">
          <section className="profile-card"><h2><AdminIcon name="users" /> Data diri</h2><p className="profile-card__subtitle">{personalTitle}</p><form className={`profile-form${key === 'anggota' ? ' profile-form--member' : ''}`} onSubmit={savePersonal}><Field label={key === 'anggota' ? 'Nama lengkap' : 'Nama penanggung jawab'} name="name" value={personal.name} onChange={updatePersonal} /><Field label="NIM" name="nim" value={personal.nim || 'Belum tersedia'} onChange={updatePersonal} readOnly /><>{key !== 'anggota' && <Field label="Nomor WhatsApp" name="whatsapp" value={personal.whatsapp} onChange={updatePersonal} />}</><Field label="Username" name="username" value={personal.username} onChange={updatePersonal} /><button className="profile-button" type="submit"><AdminIcon name="save" /> Simpan perubahan</button></form></section>
          <section className="profile-card"><h2><AdminIcon name="refresh" /> Ubah password</h2><form className="profile-form profile-password-form" onSubmit={savePassword}><PasswordField label="Password lama" name="old" value={password.old} visible={passwordVisibility.old} placeholder="Masukkan password saat ini" onChange={(e) => setPassword({ ...password, old: e.target.value })} onToggle={() => setPasswordVisibility({ ...passwordVisibility, old: !passwordVisibility.old })} /><PasswordField label="Password baru" name="next" value={password.next} visible={passwordVisibility.next} placeholder="Minimal 8 karakter" onChange={(e) => setPassword({ ...password, next: e.target.value })} onToggle={() => setPasswordVisibility({ ...passwordVisibility, next: !passwordVisibility.next })} /><PasswordField label="Konfirmasi password baru" name="confirm" value={password.confirm} visible={passwordVisibility.confirm} placeholder="Ulangi password baru" onChange={(e) => setPassword({ ...password, confirm: e.target.value })} onToggle={() => setPasswordVisibility({ ...passwordVisibility, confirm: !passwordVisibility.confirm })} /><button className="profile-button" type="submit"><LockIcon /> Ubah password</button></form></section>
          {message && <p className="profile-message" role="status">{message}</p>}
        </div>
      </div>
    </main>
  )

  return content
}

export default OrganizationProfilePage
