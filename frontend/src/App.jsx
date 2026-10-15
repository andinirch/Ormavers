import { Route, Routes } from 'react-router-dom'
import AuthLayout from './components/AuthLayout.jsx'
import HomePage from './pages/HomePage.jsx'
import LoginPage from './pages/LoginPage.jsx'
import RegisterBemPage from './pages/RegisterBemPage.jsx'
import RegisterHmjUkmPage from './pages/RegisterHmjUkmPage.jsx'
import RegisterAnggotaPage from './pages/RegisterAnggotaPage.jsx'
import NotFoundPage from './pages/NotFoundPage.jsx'

function App() {
  return (
    <Routes>
      <Route element={<AuthLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/registrasi/bem" element={<RegisterBemPage />} />
        <Route path="/registrasi/hmj-ukm" element={<RegisterHmjUkmPage />} />
        <Route path="/registrasi/anggota" element={<RegisterAnggotaPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  )
}

export default App
