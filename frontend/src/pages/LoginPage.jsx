import { useState } from 'react'
import { Link } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import { isNonEmpty, requiredError } from '../validation/rules.js'

function LoginPage() {
  const [values, setValues] = useState({ username: '', password: '' })
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState('idle')

  const handleChange = (event) => {
    const { name, value } = event.target
    setValues((prev) => ({ ...prev, [name]: value }))
    setErrors((prev) => ({ ...prev, [name]: undefined }))
  }

  const handleSubmit = (event) => {
    event.preventDefault()
    const nextErrors = {}
    if (!isNonEmpty(values.username)) {
      nextErrors.username = requiredError('Username')
    }
    if (!isNonEmpty(values.password)) {
      nextErrors.password = requiredError('Password')
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
      <h1>Login</h1>
      <p className="auth-lead">
        Masuk ke ORMAVERS menggunakan username dan password.
      </p>
      <form className="auth-form" onSubmit={handleSubmit} noValidate>
        <TextInput
          id="username"
          name="username"
          label="Username"
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
          value={values.password}
          onChange={handleChange}
          required
          autoComplete="current-password"
          error={errors.password}
        />
        {status === 'unavailable' && (
          <FormMessage variant="info">
            Data login Anda sudah lengkap. Fitur login akan aktif setelah
            integrasi backend tersedia.
          </FormMessage>
        )}
        <div className="form-actions">
          <SubmitButton>Masuk</SubmitButton>
        </div>
      </form>
      <div className="auth-register">
        <p className="auth-register__title">Belum memiliki akun?</p>
        <div className="auth-actions">
          <Link to="/registrasi/bem">Daftar sebagai BEM</Link>
          <Link to="/registrasi/hmj-ukm">Daftar sebagai HMJ/UKM</Link>
          <Link to="/registrasi/anggota">Daftar sebagai Anggota</Link>
        </div>
      </div>
    </>
  )
}

export default LoginPage
