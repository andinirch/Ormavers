import { useEffect, useMemo, useState } from 'react'
import { useAuth } from '../context/AuthContext.jsx'
import { getProfile, updatePassword, updateProfile, uploadProfilePhoto } from '../api/profile.js'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import '../styles/profile.css'

const fixtures = {
  bem: {
    roleLabel: 'Akun Organisasi BEM', displayName: 'Akun BEM UMSURA', initials: 'BF', personalTitle: 'Data penanggung jawab organisasi',
    personal: { name: 'Ahmad Fauzi Pratama', nim: '2022410012', whatsapp: '081298765432', username: 'bem_umsurabaya' },
    organization: { name: 'BEM UMSURA', campus: 'Universitas Muhammadiyah Surabaya', phone: '081234567890' },
  },
  hmj: {
    roleLabel: 'Akun Organisasi HMJ/UKM', displayName: 'Akun HMJ SI', initials: 'SI', personalTitle: 'Data penanggung jawab organisasi',
    personal: { name: 'Dimas Bayu Anggoro', nim: '2022510045', whatsapp: '081298765432', username: 'hmj_si_umsurabaya' },
    organization: { name: 'HMJ Sistem Informasi', campus: 'Universitas Muhammadiyah Surabaya', parent: 'BEM UMSURA', type: 'HMJ', phone: '081245678910' },
  },
  anggota: {
    roleLabel: 'Anggota', displayName: 'Anindya Putri Kirana', initials: 'AP', personalTitle: 'Data diri anggota',
    personal: { name: 'Anindya Putri Kirana', nim: '2023510088', username: 'anindya.kirana' },
    affiliations: ['BEM UMG', 'Himpunan Mahasiswa Informatika (HMIF)'],
    otherOrganizations: ['UKM Robotika Teknik', 'UKM Tapak Suci'],
  },
}

function roleKey(user, roles) {
  const role = user?.peran || roles?.[0]?.name || roles?.[0]
  if (role === 'akun_hmj_ukm') return 'hmj'
  if (role === 'anggota' || role === 'member') return 'anggota'
  return 'bem'
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

function KeyIcon() {
  return <svg className="profile-button__icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="3.5" /><path d="m10.5 12.5 8-8M15.5 7.5l2 2M13 10l2 2" /></svg>
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
  const fixture = fixtures[key]
  const [personal, setPersonal] = useState(fixture.personal)
  const [photoUrl, setPhotoUrl] = useState(null)
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
      setMessage('Data berhasil diperbarui.')
    } catch (error) {
      setMessage(error.response?.data?.message ?? 'Data gagal diperbarui.')
    } finally {
      setSaving(false)
    }
  }
  const savePassword = async (event) => {
    event.preventDefault()
    if (password.next.length < 8) return setMessage('Password baru minimal 8 karakter.')
    if (password.next !== password.confirm) return setMessage('Konfirmasi password tidak cocok.')
    setSaving(true)
    setMessage(null)
    try {
      await updatePassword({ password_lama: password.old, password_baru: password.next, konfirmasi_password: password.confirm })
      setPassword({ old: '', next: '', confirm: '' })
      setMessage('Password berhasil diubah.')
    } catch (error) {
      setMessage(error.response?.data?.message ?? error.response?.data?.errors?.password_lama?.[0] ?? 'Password gagal diubah.')
    } finally {
      setSaving(false)
    }
  }

  const handlePhotoChange = async (event) => {
    const file = event.target.files?.[0]
    if (!file) return
    setSaving(true)
    try {
      const result = await uploadProfilePhoto(file)
      const data = result?.data ?? result
      setPhotoUrl(data?.foto_profil_url ?? photoUrl)
      setMessage('Foto profil berhasil diubah.')
    } catch (error) {
      setMessage(error.response?.data?.message ?? 'Foto profil gagal diubah.')
    } finally {
      setSaving(false)
    }
  }
  useEffect(() => {
    let active = true
    getProfile().then((result) => {
      if (!active) return
      const data = result?.data ?? result
      setPersonal((current) => ({ ...current, name: data?.nama ?? current.name, whatsapp: data?.nomor_whatsapp ?? current.whatsapp, username: data?.username ?? current.username }))
      setPhotoUrl(data?.foto_profil_url ?? null)
    }).catch(() => {
      if (active) setMessage('Data profil gagal dimuat. Menggunakan data tampilan sementara.')
    }).finally(() => {
      if (active) setLoading(false)
    })
    return () => { active = false }
  }, [])

  const roleName = useMemo(() => fixture.roleLabel, [fixture.roleLabel])
  const organizationLabels = { name: 'Nama organisasi', campus: 'Kampus', parent: 'BEM induk', type: 'Jenis', phone: 'No. telepon' }
  const bemOrganizationLabels = { name: 'Nama organisasi', campus: 'Kampus', phone: 'No. telepon' }

  return (
    <main className="admin-page profile-page">
      <div className="profile-page__heading"><h1>Profil Saya</h1><p>Kelola data diri, password, dan foto profil</p></div>
      {loading && <p className="profile-message" role="status">Memuat data profil...</p>}
      <div className="profile-layout">
        <div className="profile-side">
          <section className="profile-card profile-summary"><div className="profile-avatar">{photoUrl ? <img src={photoUrl} alt="Foto profil" /> : fixture.initials}</div><h2>{fixture.displayName}</h2><span className={`profile-role${key === 'anggota' ? ' profile-role--member' : ''}`}>{key === 'anggota' && <span className="profile-role__dot" aria-hidden="true" />}{roleName}</span><label className="profile-button profile-button--outline"><CameraIcon /> Ubah foto profil<input className="profile-photo-input" type="file" accept="image/jpeg,image/png" onChange={handlePhotoChange} disabled={saving} /></label><small>JPG atau PNG</small></section>
          <section className={`profile-card profile-organization${key === 'anggota' ? ' profile-organization--member' : ''}`}><h2>{key === 'hmj' ? <OrganizationIcon /> : <AdminIcon name="grid" />} Organisasi</h2>{key === 'anggota' ? <div className="profile-member-org-list"><div className="profile-member-org"><MemberOrganizationIcon type="bem" /><div><small>Badan Eksekutif Mahasiswa</small><strong>{fixture.affiliations[0]}</strong></div></div><div className="profile-member-org"><MemberOrganizationIcon type="hmj" /><div><small>Himpunan Mahasiswa Jurusan (HMJ)</small><strong>{fixture.affiliations[1]}</strong></div></div></div> : Object.entries(fixture.organization).map(([label, value]) => <div className="profile-detail" key={label}><small>{key === 'bem' ? bemOrganizationLabels[label] : key === 'hmj' ? organizationLabels[label] : label === 'parent' ? 'BEM induk' : label}</small><strong>{value}</strong></div>)}</section>
          {key === 'anggota' && <section className="profile-card profile-other-organizations"><h2><span className="profile-other-organizations__title"><AdminIcon name="users" /> Organisasi lain yang diikuti</span><span className="profile-count">{fixture.otherOrganizations.length}</span></h2><p>Unit Kegiatan Mahasiswa dan komunitas aktif yang Anda ikuti.</p><div className="profile-other-list">{fixture.otherOrganizations.map((item) => <span key={item}>{item}</span>)}</div><button className="profile-button profile-button--outline" type="button"><AdminIcon name="plus" /> Tambah HMJ/UKM</button></section>}
        </div>
        <div className="profile-main">
          <section className="profile-card"><h2><AdminIcon name="users" /> Data diri</h2><p className="profile-card__subtitle">{fixture.personalTitle}</p><form className={`profile-form${key === 'anggota' ? ' profile-form--member' : ''}`} onSubmit={savePersonal}><Field label={key === 'anggota' ? 'Nama lengkap' : 'Nama penanggung jawab'} name="name" value={personal.name} onChange={updatePersonal} /><Field label="NIM" name="nim" value={personal.nim} onChange={updatePersonal} readOnly /><>{key !== 'anggota' && <Field label="Nomor WhatsApp" name="whatsapp" value={personal.whatsapp} onChange={updatePersonal} readOnly />}</><Field label="Username" name="username" value={personal.username} onChange={updatePersonal} /><button className="profile-button" type="submit"><AdminIcon name="save" /> Simpan perubahan</button></form></section>
          <section className="profile-card"><h2><AdminIcon name="refresh" /> Ubah password</h2><form className="profile-form profile-password-form" onSubmit={savePassword}><PasswordField label="Password lama" name="old" value={password.old} visible={passwordVisibility.old} placeholder="Masukkan password saat ini" onChange={(e) => setPassword({ ...password, old: e.target.value })} onToggle={() => setPasswordVisibility({ ...passwordVisibility, old: !passwordVisibility.old })} /><PasswordField label="Password baru" name="next" value={password.next} visible={passwordVisibility.next} placeholder="Minimal 8 karakter" onChange={(e) => setPassword({ ...password, next: e.target.value })} onToggle={() => setPasswordVisibility({ ...passwordVisibility, next: !passwordVisibility.next })} /><PasswordField label="Konfirmasi password baru" name="confirm" value={password.confirm} visible={passwordVisibility.confirm} placeholder="Ulangi password baru" onChange={(e) => setPassword({ ...password, confirm: e.target.value })} onToggle={() => setPasswordVisibility({ ...passwordVisibility, confirm: !passwordVisibility.confirm })} /><button className="profile-button" type="submit"><KeyIcon /> Ubah password</button></form></section>
          {message && <p className="profile-message" role="status">{message}</p>}
        </div>
      </div>
    </main>
  )
}

export default OrganizationProfilePage
