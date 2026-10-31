import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import SelectInput from '../components/form/SelectInput.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import OrmaLogo from '../components/OrmaLogo.jsx'
import Footer from '../components/Footer.jsx'
import { getOrganisasiByUniversitasAktif, getUniversitasAktif } from '../services/orgApi.js'
import { registerAnggota } from '../api/register.js'
import { simpanStatusAwal } from '../services/statusAwal.js'
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
  universitas: '',
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
        <input id="fotoKtm" name="fotoKtm" type="file" accept="image/jpeg" onChange={onChange} required className="ranggota-upload__input" aria-invalid={Boolean(error)} aria-describedby={error ? 'fotoKtm-error' : undefined} />
        <span className="ranggota-upload__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
            <path d="M7.5 18.5h8.75a4.25 4.25 0 0 0 .54-8.47A5.5 5.5 0 0 0 6.1 8.9 3.75 3.75 0 0 0 7.5 18.5Z" />
            <path d="M12 16V10.5m0 0-2.25 2.25M12 10.5l2.25 2.25" />
          </svg>
        </span>
        <strong>Unggah foto Kartu Tanda Mahasiswa (KTM)</strong>
        <small>Format file: JPG/JPEG, maksimal 1 MB</small>
        {fileName && <span className="ranggota-upload__filename">{fileName}</span>}
      </label>
      {error && <p className="form-field__error" id="fotoKtm-error" role="alert">{error}</p>}
    </div>
  )
}

function Header() {
  return (
    <header className="ranggota-header">
      <OrmaLogo to="/" className="ranggota-brand ranggota-logo" />
      <nav className="ranggota-nav" aria-label="Navigasi utama"><span className="ranggota-nav__active">Pendaftaran</span></nav>
      <div className="ranggota-header__actions"><div className="ranggota-login-group"><Link className="ranggota-login" to="/login">Masuk</Link><span className="ranggota-login-note">Sudah punya akun?</span></div><span className="ranggota-profile" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="3" /><path d="M5.5 19c.8-3.1 3.2-5 6.5-5s5.7 1.9 6.5 5" /></svg></span></div>
    </header>
  )
}

function RegisterAnggotaPage() {
  const navigate = useNavigate()
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')
  const [universitasOptions, setUniversitasOptions] = useState([])
  const [universitasLoading, setUniversitasLoading] = useState(true)
  const [orgResult, setOrgResult] = useState({ forUniversitas: null, options: [], error: null })

  useEffect(() => {
    let active = true
    getUniversitasAktif().then((items) => {
      if (!active) return
      setUniversitasOptions(items)
      setUniversitasLoading(false)
    }).catch(() => {
      if (!active) return
      setUniversitasOptions([])
      setUniversitasLoading(false)
    })
    return () => { active = false }
  }, [])

  useEffect(() => {
    const universitasId = values.universitas
    if (!universitasId) return undefined
    let active = true
    getOrganisasiByUniversitasAktif(universitasId).then((options) => {
      if (!active) return
      setOrgResult({ forUniversitas: universitasId, options })
    }).catch(() => {
      if (!active) return
      setOrgResult({ forUniversitas: universitasId, options: [] })
    })
    return () => {
      active = false
    }
  }, [values.universitas])

  const orgReady = values.universitas !== '' && orgResult.forUniversitas === values.universitas
  const hmjOptions = orgReady ? orgResult.options : []
  const hmjLoading = values.universitas !== '' && !orgReady

  const handleChange = (event) => {
    const { name, value } = event.target
    setValues((prev) => {
      const next = { ...prev, [name]: value }
      if (name === 'universitas') next.hmjUkm = ''
      return next
    })
    setErrors((prev) => {
      const next = { ...prev, [name]: undefined }
      if (name === 'universitas') next.hmjUkm = undefined
      return next
    })
  }

  const handleFileChange = (event) => {
    const file = event.target.files?.[0] ?? null
    setValues((prev) => ({ ...prev, fotoKtm: file }))
    setErrors((prev) => ({ ...prev, fotoKtm: undefined }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    const nextErrors = { ...validateRequiredFields(textFields.map((field) => ({ name: field.name, label: field.label, value: values[field.name] }))) }
    if (!values.universitas) nextErrors.universitas = requiredError('Universitas/Instansi')
    if (!values.hmjUkm) nextErrors.hmjUkm = requiredError('HMJ/UKM')
    const fotoError = validatePhotoFile(values.fotoKtm, 'Foto KTM')
    if (fotoError) nextErrors.fotoKtm = fotoError
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) {
      setStatus('idle')
      return
    }
    const formData = new FormData()
    const fields = {
      id_organisasi: values.hmjUkm,
      nama: values.nama,
      nim: values.nim,
      username: values.username,
      password: values.password,
      foto_ktm: values.fotoKtm,
    }
    Object.entries(fields).forEach(([name, value]) => formData.append(name, value))
    try {
      await registerAnggota(formData)
      simpanStatusAwal({ jenisPendaftar: 'anggota', namaPendaftar: values.nama })
      navigate('/status-registrasi', { replace: true })
    } catch (error) {
      const apiErrors = error.response?.data?.errors ?? {}
      const mappedErrors = Object.fromEntries(Object.entries(apiErrors).map(([name, messages]) => [
        { id_organisasi: 'hmjUkm', nama: 'nama', nim: 'nim', username: 'username', foto_ktm: 'fotoKtm' }[name] ?? name,
        messages[0],
      ]))
      setErrors(mappedErrors)
      setStatus('error')
    }
  }

  const universitasPlaceholder = universitasLoading ? 'Memuat daftar universitas…' : 'Pilih universitas/instansi'
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
              <SelectInput id="universitas" name="universitas" label="Universitas/Instansi" value={values.universitas} onChange={handleChange} options={universitasOptions} placeholder={universitasPlaceholder} required disabled={universitasLoading || universitasOptions.length === 0} error={errors.universitas} />
              <SelectInput id="hmjUkm" name="hmjUkm" label="HMJ/UKM/BEM" value={values.hmjUkm} onChange={handleChange} options={hmjOptions} placeholder={hmjPlaceholder} required disabled={!values.universitas || hmjLoading || hmjOptions.length === 0} error={errors.hmjUkm} />
              <div className="ranggota-info"><span aria-hidden="true">i</span><p>Pilih universitas, lalu pilih BEM, HMJ, atau UKM yang ingin Anda ikuti. Hanya organisasi terverifikasi yang muncul.</p></div>
            </section>
            <hr />
            <div className="form-actions"><SubmitButton>Kirim registrasi <span aria-hidden="true">→</span></SubmitButton></div>
            <p className="ranggota-note">Data kamu akan diperiksa oleh organisasi yang dipilih.</p>
          </form>
        </article>
      </main>
      <Footer variant="full" />
    </div>
  )
}

export default RegisterAnggotaPage
