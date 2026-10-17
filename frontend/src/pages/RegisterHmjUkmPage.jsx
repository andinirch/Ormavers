import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import SelectInput from '../components/form/SelectInput.jsx'
import RadioGroup from '../components/form/RadioGroup.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import BrandLogo from '../components/BrandLogo.jsx'
import { getVerifiedBem } from '../services/orgApi.js'
import {
  isNonEmpty,
  requiredError,
  validateDocumentFile,
  validatePhotoFile,
  validateRequiredFields,
} from '../validation/rules.js'
import '../styles/register-hmj-ukm.css'

const initialValues = {
  bemInduk: '',
  jenisOrganisasi: '',
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

const jenisOptions = [
  { value: 'hmj', label: 'HMJ (Himpunan Mahasiswa Jurusan)' },
  { value: 'ukm', label: 'UKM (Unit Kegiatan Mahasiswa)' },
]

function UploadField({ id, name, accept, onChange, fileName, error, kind, children }) {
  return (
    <div className={`rhmj-upload-field ${error ? 'has-error' : ''}`}>
      <label className={`rhmj-upload rhmj-upload--${kind}`} htmlFor={id}>
        <input
          id={id}
          name={name}
          type="file"
          accept={accept}
          onChange={onChange}
          required
          className="rhmj-upload__input"
          aria-invalid={Boolean(error)}
          aria-describedby={error ? `${id}-error` : undefined}
        />
        {children}
        {fileName && <span className="rhmj-upload__filename">{fileName}</span>}
      </label>
      {error && (
        <p className="form-field__error" id={`${id}-error`} role="alert">
          {error}
        </p>
      )}
    </div>
  )
}

function Header() {
  return (
    <header className="rhmj-header">
      <Link className="rhmj-brand" to="/">
        <BrandLogo className="rhmj-logo" />
      </Link>
      <nav className="rhmj-nav" aria-label="Navigasi utama">
        <span className="rhmj-nav__active">Pendaftaran</span>
      </nav>
      <div className="rhmj-header__actions">
        <div className="rhmj-login-group">
          <Link className="rhmj-login" to="/login">Masuk</Link>
          <span className="rhmj-login-note">Sudah punya akun?</span>
        </div>
        <span className="rhmj-profile" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="8" r="3" />
            <path d="M5.5 19c.8-3.1 3.2-5 6.5-5s5.7 1.9 6.5 5" />
          </svg>
        </span>
      </div>
    </header>
  )
}

function RegisterHmjUkmPage() {
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')
  const [bemOptions, setBemOptions] = useState([])
  const [bemLoading, setBemLoading] = useState(true)
  const [bemUnavailable, setBemUnavailable] = useState(false)

  useEffect(() => {
    let active = true
    getVerifiedBem().then((result) => {
      if (!active) return
      setBemOptions(result.data)
      setBemUnavailable(result.unavailable)
      setBemLoading(false)
    })
    return () => {
      active = false
    }
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

  const handleSubmit = (event) => {
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
    if (!isNonEmpty(values.bemInduk)) {
      nextErrors.bemInduk = requiredError('BEM induk')
    }
    if (!isNonEmpty(values.jenisOrganisasi)) {
      nextErrors.jenisOrganisasi = requiredError('Jenis organisasi')
    }
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
    setStatus('unavailable')
  }

  const bemPlaceholder = bemLoading ? 'Memuat daftar BEM…' : 'Pilih BEM terverifikasi'

  return (
    <div className="rhmj-page">
      <Header />
      <main className="rhmj-main">
        <div className="rhmj-integration-badge"><span aria-hidden="true">◉</span>PENDAFTARAN TERINTEGRASI ORMAWA JATIM</div>
        <article className="rhmj-card">
          <div className="rhmj-eyebrow"><span aria-hidden="true" />FORMULIR RESMI</div>
          <h1>Formulir registrasi HMJ/UKM</h1>
          <p className="rhmj-subtitle">Dilakukan oleh penanggung jawab HMJ/UKM</p>
          <form className="auth-form rhmj-form" onSubmit={handleSubmit} noValidate>
            {bemUnavailable && (
              <FormMessage variant="info">
                Pendaftaran HMJ/UKM belum dapat dilakukan karena daftar BEM yang sudah terverifikasi belum tersedia. Informasi ini akan diperbarui setelah integrasi backend.
              </FormMessage>
            )}
            <section className="rhmj-section">
              <h2>Pilih BEM induk</h2>
              <SelectInput id="bemInduk" name="bemInduk" label="BEM induk" value={values.bemInduk} onChange={handleChange} options={bemOptions} placeholder={bemPlaceholder} required hint="Hanya BEM yang telah terverifikasi yang tersedia" disabled={bemLoading || bemOptions.length === 0} error={errors.bemInduk} />
            </section>
            <hr />
            <section className="rhmj-section">
              <h2>Data organisasi</h2>
              <RadioGroup name="jenisOrganisasi" legend="Jenis" value={values.jenisOrganisasi} onChange={handleChange} options={jenisOptions} required error={errors.jenisOrganisasi} />
              <TextInput id="namaOrganisasi" name="namaOrganisasi" label="Nama organisasi" placeholder="Contoh: HMJ Teknik Informatika atau UKM Tapak Suci" value={values.namaOrganisasi} onChange={handleChange} required error={errors.namaOrganisasi} />
              <TextInput id="teleponOrganisasi" name="teleponOrganisasi" label="Nomor telepon organisasi" type="tel" placeholder="Contoh: 081234567890" value={values.teleponOrganisasi} onChange={handleChange} required error={errors.teleponOrganisasi} />
            </section>
            <hr />
            <section className="rhmj-section">
              <h2>Data penanggung jawab</h2>
              <TextInput id="namaPj" name="namaPj" label="Nama penanggung jawab" placeholder="Nama lengkap" value={values.namaPj} onChange={handleChange} required error={errors.namaPj} />
              <TextInput id="nim" name="nim" label="NIM" placeholder="Nomor Induk Mahasiswa" value={values.nim} onChange={handleChange} required error={errors.nim} />
              <UploadField id="foto" name="foto" accept="image/jpeg,image/png" onChange={handleFileChange('foto')} required error={errors.foto} fileName={values.foto?.name} kind="photo">
                <span className="rhmj-upload__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M7.5 18.5h8.75a4.25 4.25 0 0 0 .54-8.47A5.5 5.5 0 0 0 6.1 8.9 3.75 3.75 0 0 0 7.5 18.5Z" />
                    <path d="M12 16V10.5m0 0-2.25 2.25M12 10.5l2.25 2.25" />
                  </svg>
                </span>
                <strong>Unggah foto selfie memegang KTM</strong>
                <small>Format file: JPG atau PNG</small>
              </UploadField>
              <TextInput id="whatsapp" name="whatsapp" label="Nomor WhatsApp" type="tel" placeholder="Contoh: 0812xxxxxxxx" value={values.whatsapp} onChange={handleChange} required error={errors.whatsapp} />
              <TextInput id="username" name="username" label="Username" placeholder="Buat username akun" value={values.username} onChange={handleChange} required autoComplete="username" error={errors.username} />
              <PasswordInput id="password" name="password" label="Password" placeholder="Buat kata sandi aman" value={values.password} onChange={handleChange} required autoComplete="new-password" error={errors.password} />
            </section>
            <hr />
            <section className="rhmj-section">
              <h2>Unggah SK</h2>
              <div className="rhmj-doc-grid">
                <UploadField id="skOrganisasi" name="skOrganisasi" accept="application/pdf" onChange={handleFileChange('skOrganisasi')} required error={errors.skOrganisasi} fileName={values.skOrganisasi?.name} kind="document">
                  <span className="rhmj-upload__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M6.5 3.75h7l4 4v12.5H6.5z" />
                      <path d="M13.5 3.75v4h4M12 16.5v-5m0 0-2 2m2-2 2 2" />
                    </svg>
                  </span><strong>SK Organisasi</strong><small>PDF, maksimal 2 MB</small>
                </UploadField>
                <UploadField id="skKepengurusan" name="skKepengurusan" accept="application/pdf" onChange={handleFileChange('skKepengurusan')} required error={errors.skKepengurusan} fileName={values.skKepengurusan?.name} kind="document">
                  <span className="rhmj-upload__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M6.5 3.75h7l4 4v12.5H6.5z" />
                      <path d="M13.5 3.75v4h4M12 16.5v-5m0 0-2 2m2-2 2 2" />
                    </svg>
                  </span><strong>SK Kepengurusan saat ini</strong><small>PDF, maksimal 2 MB</small>
                </UploadField>
              </div>
            </section>
            {status === 'unavailable' && <FormMessage variant="info">Data registrasi Anda sudah lengkap. Pengiriman registrasi akan aktif setelah integrasi backend tersedia.</FormMessage>}
            <div className="form-actions"><SubmitButton>Kirim registrasi <span aria-hidden="true">→</span></SubmitButton></div>
            <p className="rhmj-note">Data kamu akan diperiksa oleh BEM induk.</p>
          </form>
        </article>
      </main>
      <footer className="rhmj-footer"><span>© 2025 Ormavers — Ekosistem Kolaborasi Ormawa Muhammadiyah Jawa Timur.</span><span><span>Panduan Layanan</span><span>Kontak Bantuan</span></span></footer>
    </div>
  )
}

export default RegisterHmjUkmPage
