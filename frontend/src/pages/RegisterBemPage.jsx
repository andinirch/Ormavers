import { useState } from 'react'
import { Link } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import FileInput from '../components/form/FileInput.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import {
  validateDocumentFile,
  validatePhotoFile,
  validateRequiredFields,
} from '../validation/rules.js'

const initialValues = {
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
  { name: 'namaInstansi', label: 'Nama instansi/kampus' },
  { name: 'namaOrganisasi', label: 'Nama organisasi' },
  { name: 'teleponOrganisasi', label: 'Nomor telepon organisasi' },
  { name: 'namaPj', label: 'Nama penanggung jawab' },
  { name: 'nim', label: 'NIM' },
  { name: 'whatsapp', label: 'Nomor WhatsApp' },
  { name: 'username', label: 'Username' },
  { name: 'password', label: 'Password' },
]

function RegisterBemPage() {
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')

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
    const fotoError = validatePhotoFile(values.foto)
    if (fotoError) {
      nextErrors.foto = fotoError
    }
    const skOrganisasiError = validateDocumentFile(
      values.skOrganisasi,
      'SK Organisasi',
    )
    if (skOrganisasiError) {
      nextErrors.skOrganisasi = skOrganisasiError
    }
    const skKepengurusanError = validateDocumentFile(
      values.skKepengurusan,
      'SK Kepengurusan',
    )
    if (skKepengurusanError) {
      nextErrors.skKepengurusan = skKepengurusanError
    }
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) {
      setStatus('idle')
      return
    }
    setStatus('unavailable')
  }

  return (
    <>
      <h1>Registrasi BEM</h1>
      <p className="auth-lead">
        Pendaftaran organisasi BEM untuk diperiksa oleh Administrator.
      </p>
      <form className="auth-form" onSubmit={handleSubmit} noValidate>
        <section className="auth-form__section">
          <h2 className="auth-form__section-title">A. Data instansi</h2>
          <TextInput
            id="namaInstansi"
            name="namaInstansi"
            label="Nama instansi/kampus"
            placeholder="Universitas Muhammadiyah Gresik"
            value={values.namaInstansi}
            onChange={handleChange}
            required
            error={errors.namaInstansi}
          />
        </section>
        <section className="auth-form__section">
          <h2 className="auth-form__section-title">B. Data organisasi</h2>
          <TextInput
            id="namaOrganisasi"
            name="namaOrganisasi"
            label="Nama organisasi"
            placeholder="BEM Universitas Muhammadiyah Gresik"
            value={values.namaOrganisasi}
            onChange={handleChange}
            required
            error={errors.namaOrganisasi}
          />
          <TextInput
            id="teleponOrganisasi"
            name="teleponOrganisasi"
            label="Nomor telepon organisasi"
            type="tel"
            placeholder="08xxxxxxxxxx"
            value={values.teleponOrganisasi}
            onChange={handleChange}
            required
            error={errors.teleponOrganisasi}
          />
        </section>
        <section className="auth-form__section">
          <h2 className="auth-form__section-title">C. Data penanggung jawab</h2>
          <TextInput
            id="namaPj"
            name="namaPj"
            label="Nama"
            placeholder="Masukkan nama lengkap"
            value={values.namaPj}
            onChange={handleChange}
            required
            error={errors.namaPj}
          />
          <TextInput
            id="nim"
            name="nim"
            label="NIM"
            placeholder="Masukkan NIM"
            value={values.nim}
            onChange={handleChange}
            required
            error={errors.nim}
          />
          <FileInput
            id="foto"
            name="foto"
            label="Foto Selfie dengan KTM"
            accept="image/jpeg,image/png"
            onChange={handleFileChange('foto')}
            required
            hint="Upload foto selfie dengan KTM (JPG/PNG). Wajah dan KTM harus terlihat jelas, dan informasi pada KTM dapat dibaca."
            error={errors.foto}
            fileName={values.foto?.name}
          />
          <TextInput
            id="whatsapp"
            name="whatsapp"
            label="Nomor WhatsApp"
            type="tel"
            placeholder="08xxxxxxxxxx"
            value={values.whatsapp}
            onChange={handleChange}
            required
            error={errors.whatsapp}
          />
          <TextInput
            id="username"
            name="username"
            label="Username"
            placeholder="Buat username"
            value={values.username}
            onChange={handleChange}
            required
            autoComplete="username"
            error={errors.username}
          />
          <PasswordInput
            id="password"
            name="password"
            label="Password"
            placeholder="Masukkan password"
            value={values.password}
            onChange={handleChange}
            required
            autoComplete="new-password"
            error={errors.password}
          />
        </section>
        <section className="auth-form__section">
          <h2 className="auth-form__section-title">D. Dokumen</h2>
          <FileInput
            id="skOrganisasi"
            name="skOrganisasi"
            label="SK Organisasi"
            onChange={handleFileChange('skOrganisasi')}
            required
            hint="Format PDF, maksimal 5 MB."
            error={errors.skOrganisasi}
            fileName={values.skOrganisasi?.name}
          />
          <FileInput
            id="skKepengurusan"
            name="skKepengurusan"
            label="SK Kepengurusan saat ini"
            onChange={handleFileChange('skKepengurusan')}
            required
            hint="Format PDF, maksimal 5 MB."
            error={errors.skKepengurusan}
            fileName={values.skKepengurusan?.name}
          />
        </section>
        {status === 'unavailable' && (
          <FormMessage variant="info">
            Data registrasi Anda sudah lengkap. Pengiriman registrasi akan aktif
            setelah integrasi backend tersedia.
          </FormMessage>
        )}
        <div className="form-actions">
          <SubmitButton>Kirim Registrasi</SubmitButton>
        </div>
      </form>
      <div className="auth-register">
        <p className="auth-register__title">Sudah memiliki akun?</p>
        <div className="auth-actions">
          <Link to="/login">Login</Link>
        </div>
      </div>
    </>
  )
}

export default RegisterBemPage
