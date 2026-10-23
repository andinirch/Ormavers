import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import SelectInput from '../components/form/SelectInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import BrandLogo from '../components/BrandLogo.jsx'
import Footer from '../components/Footer.jsx'
import {
  validateDocumentFile,
  validatePhotoFile,
  validateRequiredFields,
} from '../validation/rules.js'
import { registerBem } from '../api/register.js'
import { getUniversitas } from '../services/orgApi.js'
import '../styles/register-bem.css'

const initialValues = {
  idUniversitas: '',
  namaInstansi: '',
  namaOrganisasi: '',
  teleponOrganisasi: '',
  namaPj: '',
  nim: '',
  foto: null,
  whatsapp: '',
  username: '',
  password: '',
  skOrganisasi: null,
  skKepengurusan: null,
}

const textFields = [
  { name: 'namaOrganisasi', label: 'Nama organisasi' },
  { name: 'teleponOrganisasi', label: 'Nomor telepon organisasi' },
  { name: 'namaPj', label: 'Nama penanggung jawab' },
  { name: 'nim', label: 'NIM' },
  { name: 'whatsapp', label: 'Nomor WhatsApp' },
  { name: 'username', label: 'Username' },
  { name: 'password', label: 'Password' },
]

function UploadField({ id, name, accept, onChange, fileName, error, kind, children }) {
  return (
    <div className={`rbem-upload-field ${error ? 'has-error' : ''}`}>
      <label className={`rbem-upload rbem-upload--${kind}`} htmlFor={id}>
        <input id={id} name={name} type="file" accept={accept} onChange={onChange} required className="rbem-upload__input" aria-invalid={Boolean(error)} aria-describedby={error ? `${id}-error` : undefined} />
        {children}
        {fileName && <span className="rbem-upload__filename">{fileName}</span>}
      </label>
      {error && <p className="form-field__error" id={`${id}-error`} role="alert">{error}</p>}
    </div>
  )
}

function SectionTitle({ children }) {
  return <h2 className="rbem-section-title"><span aria-hidden="true" />{children}</h2>
}

function RegisterBemPage() {
  const navigate = useNavigate()
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')
  const [universitas, setUniversitas] = useState([])
  const [universitasLoading, setUniversitasLoading] = useState(true)

  useEffect(() => {
    let active = true
    getUniversitas().then((data) => {
      if (active) setUniversitas(data)
    }).catch(() => {
      if (active) setErrors((current) => ({ ...current, idUniversitas: 'Daftar universitas gagal dimuat.' }))
    }).finally(() => {
      if (active) setUniversitasLoading(false)
    })
    return () => { active = false }
  }, [])

  const handleChange = (event) => {
    const { name, value } = event.target
    setValues((prev) => ({ ...prev, [name]: value }))
    setErrors((prev) => ({ ...prev, [name]: undefined }))
  }

  const handleFileChange = (name) => (event) => {
    const file = event.target.files?.[0] ?? null
    setValues((prev) => ({ ...prev, [name]: file }))
    setErrors((prev) => ({ ...prev, [name]: undefined }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    const nextErrors = {
      ...validateRequiredFields(
        textFields.map((field) => ({
          name: field.name,
          label: field.label,
          value: values[field.name],
        })),
      ),
    }
    if (!values.idUniversitas) nextErrors.idUniversitas = 'Universitas wajib dipilih.'
    const fotoError = validatePhotoFile(values.foto)
    if (fotoError) nextErrors.foto = fotoError
    const skOrganisasiError = validateDocumentFile(values.skOrganisasi, 'SK Organisasi')
    if (skOrganisasiError) nextErrors.skOrganisasi = skOrganisasiError
    const skKepengurusanError = validateDocumentFile(values.skKepengurusan, 'SK Kepengurusan')
    if (skKepengurusanError) nextErrors.skKepengurusan = skKepengurusanError
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) {
      setStatus('idle')
      return
    }
    const formData = new FormData()
    const fields = {
      id_universitas: values.idUniversitas,
      nama_organisasi: values.namaOrganisasi,
      nomor_telepon_organisasi: values.teleponOrganisasi,
      nama: values.namaPj,
      nim: values.nim,
      nomor_whatsapp: values.whatsapp,
      username: values.username,
      password: values.password,
      foto_ktm: values.foto,
      sk_organisasi: values.skOrganisasi,
      sk_kepengurusan: values.skKepengurusan,
    }
    Object.entries(fields).forEach(([name, value]) => formData.append(name, value))
    try {
      await registerBem(formData)
      navigate('/login?redirect=/status-registrasi', { replace: true })
    } catch (error) {
      const apiErrors = error.response?.data?.errors ?? {}
      const mappedErrors = Object.fromEntries(Object.entries(apiErrors).map(([name, messages]) => [
        { id_universitas: 'idUniversitas', nama_organisasi: 'namaOrganisasi', nomor_telepon_organisasi: 'teleponOrganisasi', nama: 'namaPj', nomor_whatsapp: 'whatsapp', foto_ktm: 'foto', sk_organisasi: 'skOrganisasi', sk_kepengurusan: 'skKepengurusan' }[name] ?? name,
        messages[0],
      ]))
      setErrors(mappedErrors)
      setStatus('error')
    }
  }

  return (
    <div className="rbem-page">
      <header className="rbem-header">
        <Link className="rbem-brand" to="/">
          <BrandLogo className="rbem-logo" />
        </Link>
        <nav className="rbem-nav" aria-label="Navigasi utama">
          <span className="rbem-nav__active">Pendaftaran</span>
        </nav>
        <div className="rbem-header__actions">
          <div className="rbem-login-group">
            <Link className="rbem-login" to="/login">Masuk</Link>
            <span className="rbem-login-note">Sudah punya akun?</span>
          </div>
          <span className="rbem-profile" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="12" cy="8" r="3" />
              <path d="M5.5 19c.8-3.1 3.2-5 6.5-5s5.7 1.9 6.5 5" />
            </svg>
          </span>
        </div>
      </header>

      <main className="rbem-main">
        <article className="rbem-card">
          <div className="rbem-badge"><span aria-hidden="true">✦</span>Pendaftaran Resmi Wilayah Jawa Timur</div>
          <h1>Formulir registrasi BEM</h1>
          <p className="rbem-subtitle">Dilakukan oleh penanggung jawab BEM</p>
          <form className="auth-form rbem-form" onSubmit={handleSubmit} noValidate>
            <section className="rbem-section">
              <SectionTitle>Data instansi</SectionTitle>
              <SelectInput id="idUniversitas" name="idUniversitas" label="Nama instansi/kampus" value={values.idUniversitas} onChange={handleChange} options={universitas} required disabled={universitasLoading || universitas.length === 0} placeholder={universitasLoading ? 'Memuat daftar universitas…' : 'Pilih universitas'} error={errors.idUniversitas} hint="Pilih universitas organisasi kamu" />
            </section>
            <hr />
            <section className="rbem-section">
              <SectionTitle>Data organisasi</SectionTitle>
              <TextInput id="namaOrganisasi" name="namaOrganisasi" label="Nama organisasi" placeholder="Contoh: BEM Universitas Muhammadiyah Gresik" value={values.namaOrganisasi} onChange={handleChange} required error={errors.namaOrganisasi} />
              <TextInput id="teleponOrganisasi" name="teleponOrganisasi" label="Nomor telepon organisasi" type="tel" placeholder="Contoh: 081234567890" value={values.teleponOrganisasi} onChange={handleChange} required error={errors.teleponOrganisasi} />
            </section>
            <hr />
            <section className="rbem-section">
              <SectionTitle>Data penanggung jawab</SectionTitle>
              <div className="rbem-grid">
                <TextInput id="namaPj" name="namaPj" label="Nama penanggung jawab" placeholder="Nama lengkap" value={values.namaPj} onChange={handleChange} required error={errors.namaPj} />
                <TextInput id="nim" name="nim" label="NIM" placeholder="Nomor Induk Mahasiswa" value={values.nim} onChange={handleChange} required error={errors.nim} />
              </div>
              <UploadField id="foto" name="foto" label="Foto Selfie dengan KTM" accept="image/jpeg,image/png" onChange={handleFileChange('foto')} required error={errors.foto} fileName={values.foto?.name} kind="photo">
                <span className="rbem-upload__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M7.5 18.5h8.75a4.25 4.25 0 0 0 .54-8.47A5.5 5.5 0 0 0 6.1 8.9 3.75 3.75 0 0 0 7.5 18.5Z" />
                    <path d="M12 16V10.5m0 0-2.25 2.25M12 10.5l2.25 2.25" />
                  </svg>
                </span><strong>Unggah foto selfie memegang KTM</strong><small>Format file: JPG atau PNG</small>
              </UploadField>
              <TextInput id="whatsapp" name="whatsapp" label="Nomor WhatsApp" type="tel" placeholder="Contoh: 0812xxxxxxxx" value={values.whatsapp} onChange={handleChange} required error={errors.whatsapp} />
              <div className="rbem-grid">
                <TextInput id="username" name="username" label="Username" placeholder="Buat username akun" value={values.username} onChange={handleChange} required autoComplete="username" error={errors.username} />
                <PasswordInput id="password" name="password" label="Password" placeholder="Buat kata sandi aman" value={values.password} onChange={handleChange} required autoComplete="new-password" error={errors.password} />
              </div>
            </section>
            <hr />
            <section className="rbem-section">
              <SectionTitle>Unggah SK</SectionTitle>
              <div className="rbem-grid">
                <UploadField id="skOrganisasi" name="skOrganisasi" label="SK Organisasi" accept="application/pdf" onChange={handleFileChange('skOrganisasi')} error={errors.skOrganisasi} fileName={values.skOrganisasi?.name} kind="document"><span className="rbem-upload__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round"><path d="M6.5 3.75h7l4 4v12.5H6.5z" /><path d="M13.5 3.75v4h4M12 16.5v-5m0 0-2 2m2-2 2 2" /></svg></span><strong>SK Organisasi</strong><small>PDF, maksimal 2 MB</small></UploadField>
                <UploadField id="skKepengurusan" name="skKepengurusan" label="SK Kepengurusan saat ini" accept="application/pdf" onChange={handleFileChange('skKepengurusan')} error={errors.skKepengurusan} fileName={values.skKepengurusan?.name} kind="document"><span className="rbem-upload__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round"><path d="M6.5 3.75h7l4 4v12.5H6.5z" /><path d="M13.5 3.75v4h4M12 16.5v-5m0 0-2 2m2-2 2 2" /></svg></span><strong>SK Kepengurusan saat ini</strong><small>PDF, maksimal 2 MB</small></UploadField>
              </div>
            </section>
             {status === 'error' && <FormMessage variant="error">Registrasi gagal. Periksa kembali data yang dimasukkan.</FormMessage>}
            <div className="form-actions"><SubmitButton>Kirim registrasi <span aria-hidden="true">→</span></SubmitButton></div>
            <p className="rbem-note">Data kamu akan diperiksa oleh Administrator.</p>
          </form>
        </article>
      </main>
      <Footer variant="full" />
    </div>
  )
}

export default RegisterBemPage
