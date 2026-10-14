import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import SelectInput from '../components/form/SelectInput.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import { getVerifiedBem, getVerifiedHmjUkm } from '../services/orgApi.js'
import {
  requiredError,
  validatePhotoFile,
  validateRequiredFields,
} from '../validation/rules.js'
import '../styles/register-anggota.css'

const initialValues = {
  nama: '',
  nim: '',
  username: '',
  password: '',
  bem: '',
  hmjUkm: '',
  fotoKtm: null,
}

const textFields = [
  { name: 'nama', label: 'Nama' },
  { name: 'nim', label: 'NIM' },
  { name: 'username', label: 'Username' },
  { name: 'password', label: 'Password' },
]

function UploadKtm({ fileName, error, onChange }) {
  return (
    <div className={`ranggota-upload-field ${error ? 'has-error' : ''}`}>
      <label className="ranggota-upload" htmlFor="fotoKtm">
        <input id="fotoKtm" name="fotoKtm" type="file" accept="image/jpeg,image/png" onChange={onChange} required className="ranggota-upload__input" aria-invalid={Boolean(error)} aria-describedby={error ? 'fotoKtm-error' : undefined} />
        <span className="ranggota-upload__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
            <path d="M7.5 18.5h8.75a4.25 4.25 0 0 0 .54-8.47A5.5 5.5 0 0 0 6.1 8.9 3.75 3.75 0 0 0 7.5 18.5Z" />
            <path d="M12 16V10.5m0 0-2.25 2.25M12 10.5l2.25 2.25" />
          </svg>
        </span>
        <strong>Unggah foto Kartu Tanda Mahasiswa (KTM)</strong>
        <small>Format file: JPG atau PNG</small>
        {fileName && <span className="ranggota-upload__filename">{fileName}</span>}
      </label>
      {error && <p className="form-field__error" id="fotoKtm-error" role="alert">{error}</p>}
    </div>
  )
}

function Header() {
  return (
    <header className="ranggota-header">
      <Link className="ranggota-brand" to="/"><span className="ranggota-brand__mark">O</span><span>Ormavers</span></Link>
      <nav className="ranggota-nav" aria-label="Navigasi utama"><span className="ranggota-nav__active">Pendaftaran</span></nav>
      <div className="ranggota-header__actions"><div className="ranggota-login-group"><Link className="ranggota-login" to="/login">Masuk</Link><span className="ranggota-login-note">Sudah punya akun?</span></div><span className="ranggota-profile" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="3" /><path d="M5.5 19c.8-3.1 3.2-5 6.5-5s5.7 1.9 6.5 5" /></svg></span></div>
    </header>
  )
}

function RegisterAnggotaPage() {
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')
  const [bemOptions, setBemOptions] = useState([])
  const [bemLoading, setBemLoading] = useState(true)
  const [bemUnavailable, setBemUnavailable] = useState(false)
  const [hmjResult, setHmjResult] = useState({ forBem: null, options: [], unavailable: false })

  useEffect(() => {
    let active = true
    getVerifiedBem().then((result) => {
      if (!active) return
      setBemOptions(result.data)
      setBemUnavailable(result.unavailable)
      setBemLoading(false)
    })
    return () => { active = false }
  }, [])

  useEffect(() => {
    const bemId = values.bem
    if (!bemId) return undefined
    let active = true
    const selectedBem = bemOptions.find((option) => option.value === bemId)
    getVerifiedHmjUkm(bemId, selectedBem).then((result) => {
      if (!active) return
      setHmjResult({ forBem: bemId, options: result.data, unavailable: result.unavailable })
    })
    return () => { active = false }
  }, [values.bem, bemOptions])

  const hmjReady = values.bem !== '' && hmjResult.forBem === values.bem
  const hmjOptions = hmjReady ? hmjResult.options : []
  const hmjUnavailable = hmjReady && hmjResult.unavailable
  const hmjLoading = values.bem !== '' && !hmjReady

  const handleChange = (event) => {
    const { name, value } = event.target
    setValues((prev) => {
      const next = { ...prev, [name]: value }
      if (name === 'bem') next.hmjUkm = ''
      return next
    })
    setErrors((prev) => {
      const next = { ...prev, [name]: undefined }
      if (name === 'bem') next.hmjUkm = undefined
      return next
    })
  }

  const handleFileChange = (event) => {
    const file = event.target.files?.[0] ?? null
    setValues((prev) => ({ ...prev, fotoKtm: file }))
    setErrors((prev) => ({ ...prev, fotoKtm: undefined }))
  }

  const handleSubmit = (event) => {
    event.preventDefault()
    const nextErrors = { ...validateRequiredFields(textFields.map((field) => ({ name: field.name, label: field.label, value: values[field.name] }))) }
    if (!values.bem) nextErrors.bem = requiredError('BEM')
    if (!values.hmjUkm) nextErrors.hmjUkm = requiredError('HMJ/UKM')
    const fotoError = validatePhotoFile(values.fotoKtm, 'Foto KTM')
    if (fotoError) nextErrors.fotoKtm = fotoError
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) {
      setStatus('idle')
      return
    }
    setStatus('unavailable')
  }

  const bemPlaceholder = bemLoading ? 'Memuat daftar BEM…' : 'Pilih BEM'
  const hmjPlaceholder = hmjLoading ? 'Memuat daftar HMJ/UKM/BEM…' : 'Pilih sebagai anggota HMJ/UKM/BEM'

  return (
    <div className="ranggota-page">
      <Header />
      <main className="ranggota-main">
        <article className="ranggota-card">
          <div className="ranggota-eyebrow"><span aria-hidden="true" />PORTAL ORMAWA MUHAMMADIYAH</div>
          <h1>Formulir registrasi anggota</h1>
          <p className="ranggota-subtitle">Dilakukan individu yang akan bergabung ke organisasi</p>
          <form className="auth-form ranggota-form" onSubmit={handleSubmit} noValidate>
            {bemUnavailable && <FormMessage variant="info">Pilihan organisasi belum tersedia. Pendaftaran anggota dapat dilakukan setelah daftar BEM yang sudah terverifikasi tersedia. Informasi ini akan diperbarui setelah integrasi backend.</FormMessage>}
            <section className="ranggota-section">
              <div className="ranggota-section-heading"><h2>Data diri</h2><span>Langkah Wajib</span></div>
              <TextInput id="nama" name="nama" label="Nama" placeholder="Nama lengkap mahasiswa" value={values.nama} onChange={handleChange} required error={errors.nama} />
              <TextInput id="nim" name="nim" label="NIM" placeholder="Nomor Induk Mahasiswa" value={values.nim} onChange={handleChange} required error={errors.nim} />
              <UploadKtm fileName={values.fotoKtm?.name} error={errors.fotoKtm} onChange={handleFileChange} />
              <TextInput id="username" name="username" label="Username" placeholder="Buat username" value={values.username} onChange={handleChange} required autoComplete="username" error={errors.username} />
              <PasswordInput id="password" name="password" label="Password" placeholder="Buat kata sandi" value={values.password} onChange={handleChange} required autoComplete="new-password" error={errors.password} />
            </section>
            <hr />
            <section className="ranggota-section">
              <div className="ranggota-section-heading"><h2>Pilih organisasi</h2><span>Afiliasi Kampus</span></div>
              <SelectInput id="bem" name="bem" label="BEM" value={values.bem} onChange={handleChange} options={bemOptions} placeholder={bemPlaceholder} required disabled={bemLoading || bemOptions.length === 0} error={errors.bem} />
              {hmjUnavailable && <FormMessage variant="info">Pilihan HMJ/UKM di bawah BEM yang dipilih belum tersedia.</FormMessage>}
              <SelectInput id="hmjUkm" name="hmjUkm" label="HMJ/UKM/BEM" value={values.hmjUkm} onChange={handleChange} options={hmjOptions} placeholder={hmjPlaceholder} required disabled={!values.bem || hmjLoading || hmjOptions.length === 0} error={errors.hmjUkm} />
              <div className="ranggota-info"><span aria-hidden="true">i</span><p>Pilih BEM, lalu pilih sebagai anggota HMJ/UKM/BEM di bawah BEM tersebut. Hanya organisasi terverifikasi yang muncul.</p></div>
            </section>
            {status === 'unavailable' && <FormMessage variant="info">Data registrasi Anda sudah lengkap. Pengiriman registrasi akan aktif setelah integrasi backend tersedia.</FormMessage>}
            <hr />
            <div className="form-actions"><SubmitButton>Kirim registrasi <span aria-hidden="true">→</span></SubmitButton></div>
            <p className="ranggota-note">Data kamu akan diperiksa oleh organisasi yang dipilih.</p>
          </form>
        </article>
      </main>
      <footer className="ranggota-footer"><span>© 2025 Ormavers — Ekosistem Kolaborasi Ormawa Muhammadiyah Jawa Timur.</span><span><span>Panduan Layanan</span><span>Kontak Bantuan</span></span></footer>
    </div>
  )
}

export default RegisterAnggotaPage
