import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import SelectInput from '../components/form/SelectInput.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import { getVerifiedBem, getVerifiedHmjUkm } from '../services/orgApi.js'
import { requiredError, validateRequiredFields } from '../validation/rules.js'

// TODO(ORMV-1-03): komposisi field final Registrasi Anggota menunggu
// konfirmasi bagian ORMV-1-03 pada SKPL dan wireframe final. Hanya field
// yang jelas dari PRD yang diisi di sini; field lain (mis. jenis foto KTM)
// tidak boleh ditebak sampai dikonfirmasi.

const initialValues = {
  nama: '',
  nim: '',
  username: '',
  password: '',
  bem: '',
  hmjUkm: '',
}

const textFields = [
  { name: 'nama', label: 'Nama' },
  { name: 'nim', label: 'NIM' },
  { name: 'username', label: 'Username' },
  { name: 'password', label: 'Password' },
]

function RegisterAnggotaPage() {
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')
  const [bemOptions, setBemOptions] = useState([])
  const [bemLoading, setBemLoading] = useState(true)
  const [bemUnavailable, setBemUnavailable] = useState(false)
  const [hmjResult, setHmjResult] = useState({
    forBem: null,
    options: [],
    unavailable: false,
  })

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

  useEffect(() => {
    const bemId = values.bem
    if (!bemId) {
      return undefined
    }
    let active = true
    getVerifiedHmjUkm(bemId).then((result) => {
      if (!active) return
      setHmjResult({
        forBem: bemId,
        options: result.data,
        unavailable: result.unavailable,
      })
    })
    return () => {
      active = false
    }
  }, [values.bem])

  const hmjReady = values.bem !== '' && hmjResult.forBem === values.bem
  const hmjOptions = hmjReady ? hmjResult.options : []
  const hmjUnavailable = hmjReady && hmjResult.unavailable
  const hmjLoading = values.bem !== '' && !hmjReady

  const handleChange = (event) => {
    const { name, value } = event.target
    setValues((prev) => {
      const next = { ...prev, [name]: value }
      if (name === 'bem') {
        next.hmjUkm = ''
      }
      return next
    })
    setErrors((prev) => {
      const next = { ...prev, [name]: undefined }
      if (name === 'bem') {
        next.hmjUkm = undefined
      }
      return next
    })
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
    if (!values.bem) {
      nextErrors.bem = requiredError('BEM')
    }
    if (!values.hmjUkm) {
      nextErrors.hmjUkm = requiredError('HMJ/UKM')
    }
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) {
      setStatus('idle')
      return
    }
    setStatus('unavailable')
  }

  const bemPlaceholder = bemLoading
    ? 'Memuat daftar BEM…'
    : 'Pilih BEM'
  const hmjPlaceholder = hmjLoading
    ? 'Memuat daftar HMJ/UKM…'
    : 'Pilih HMJ/UKM'

  return (
    <>
      <h1>Registrasi Anggota</h1>
      <p className="auth-lead">
        Pendaftaran calon anggota dengan memilih organisasi yang sudah
        terverifikasi.
      </p>
      <form className="auth-form" onSubmit={handleSubmit} noValidate>
        {bemUnavailable && (
          <FormMessage variant="info">
            Pilihan organisasi belum tersedia. Pendaftaran anggota dapat
            dilakukan setelah daftar BEM yang sudah terverifikasi tersedia.
            Informasi ini akan diperbarui setelah integrasi backend.
          </FormMessage>
        )}
        <section className="auth-form__section">
          <h2 className="auth-form__section-title">A. Data diri</h2>
          <TextInput
            id="nama"
            name="nama"
            label="Nama"
            placeholder="Masukkan nama lengkap"
            value={values.nama}
            onChange={handleChange}
            required
            error={errors.nama}
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
        </section>
        <section className="auth-form__section">
          <h2 className="auth-form__section-title">B. Organisasi</h2>
          <SelectInput
            id="bem"
            name="bem"
            label="BEM"
            value={values.bem}
            onChange={handleChange}
            options={bemOptions}
            placeholder={bemPlaceholder}
            required
            disabled={bemLoading || bemOptions.length === 0}
            error={errors.bem}
          />
          {hmjUnavailable && (
            <FormMessage variant="info">
              Pilihan HMJ/UKM di bawah BEM yang dipilih belum tersedia.
            </FormMessage>
          )}
          <SelectInput
            id="hmjUkm"
            name="hmjUkm"
            label="HMJ/UKM"
            value={values.hmjUkm}
            onChange={handleChange}
            options={hmjOptions}
            placeholder={hmjPlaceholder}
            required
            disabled={!values.bem || hmjLoading || hmjOptions.length === 0}
            error={errors.hmjUkm}
          />
        </section>
        <section className="auth-form__section">
          <h2 className="auth-form__section-title">C. Akun</h2>
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

export default RegisterAnggotaPage
