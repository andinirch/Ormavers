import { useState } from 'react'
import { Link } from 'react-router-dom'
import TextInput from '../components/form/TextInput.jsx'
import PasswordInput from '../components/form/PasswordInput.jsx'
import SubmitButton from '../components/form/SubmitButton.jsx'
import FormMessage from '../components/form/FormMessage.jsx'
import { isNonEmpty, requiredError } from '../validation/rules.js'
import '../styles/login.css'

function BrandMark() {
  return (
    <svg className="login-logo__mark" viewBox="0 0 32 32" aria-hidden="true">
      <rect width="32" height="32" rx="9" fill="currentColor" />
      <path d="m16 7 2.1 5.2L23 14l-4.9 2.1L16 21l-2.1-4.9L9 14l4.9-1.8L16 7Z" fill="white" />
      <circle cx="16" cy="14" r="2.2" fill="currentColor" />
    </svg>
  )
}

function VerifiedMark() {
  return (
    <svg className="login-hero__verified" viewBox="0 0 24 24" aria-hidden="true">
      <path d="m12 3 2 1.4 2.4-.1 1.1 2.1 2.1 1.1-.1 2.4 1.4 2-1.4 2 .1 2.4-2.1 1.1-1.1 2.1-2.4-.1-2 1.4-2-1.4-2.4.1-1.1-2.1-2.1-1.1.1-2.4-1.4-2 1.4-2-.1-2.4 2.1-1.1 1.1-2.1 2.4.1L12 3Z" fill="none" stroke="currentColor" strokeWidth="1.7" />
      <path d="m8.5 12.2 2.2 2.2 4.8-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

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
    <div className="login-page">
      <header className="login-header">
        <div className="login-header__brand">
          <BrandMark />
          <span>Ormavers</span>
        </div>
        <span className="login-header__title">Ormavers</span>
      </header>

      <main className="login-main">
        <section className="login-hero" aria-label="Tentang Ormavers">
          <div className="login-hero__dots" aria-hidden="true" />
          <span className="login-hero__badge">PTMA JAWA TIMUR</span>
          <div className="login-hero__decor" aria-hidden="true">
            <span className="login-hero__circle login-hero__circle--top" />
            <span className="login-hero__square" />
            <span className="login-hero__pill" />
            <span className="login-hero__circle login-hero__circle--bottom" />
          </div>
          <div className="login-hero__copy">
            <h1 className="login-hero__title">
              Satu platform<br />
              untuk organisasi<br />
              mahasiswa PTMA<br />
              Jawa Timur
            </h1>
            <p className="login-hero__description">
              Kolaborasi terintegrasi, transparan, dan terpercaya bagi BEM,<br />
              HMJ, dan UKM di lingkungan Perguruan Tinggi Muhammadiyah<br />
              &amp; &apos;Aisyiyah Jawa Timur.
            </p>
          </div>
          <div className="login-hero__footer">
            <VerifiedMark />
            <span>Ekosistem Digital Resmi Ormawa PTMA Jatim</span>
          </div>
        </section>

        <section className="login-side">
          <div className="login-card">
            <h1 className="login-card__title">Masuk ke Ormavers</h1>
            <p className="login-card__subtitle">
              Platform organisasi mahasiswa PTMA Jawa Timur
            </p>
            <form className="auth-form login-form" onSubmit={handleSubmit} noValidate>
              <TextInput
                id="username"
                name="username"
                label="Username"
                placeholder="Masukkan username atau email"
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
                placeholder="Masukkan kata sandi"
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
            <p className="login-card__alternative">Belum punya akun?</p>
            <div className="login-register-links">
              <Link to="/registrasi/bem">Daftar BEM</Link>
              <Link to="/registrasi/hmj-ukm">Daftar<br />HMJ/UKM</Link>
              <Link to="/registrasi/anggota">Daftar<br />anggota</Link>
            </div>
          </div>
        </section>
      </main>
    </div>
  )
}

export default LoginPage
