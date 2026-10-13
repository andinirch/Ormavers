import { Route, Routes } from 'react-router-dom'
import AppShell from './layouts/AppShell.jsx'
import RegistrationStatusPage from './pages/RegistrationStatusPage.jsx'
import NotFoundPage from './pages/NotFoundPage.jsx'
import VerificationShell from './layouts/VerificationShell.jsx'
import BemVerificationPage from './pages/BemVerificationPage.jsx'
import HmjUkmVerificationPage from './pages/HmjUkmVerificationPage.jsx'
import MemberVerificationPage from './pages/MemberVerificationPage.jsx'

function App() {
  return (
    <Routes>
      <Route element={<AppShell />}>
        <Route path="/" element={<RegistrationStatusPage />} />
        <Route path="/status-registrasi" element={<RegistrationStatusPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
      <Route element={<VerificationShell />}>
        <Route path="/verifikasi/bem" element={<BemVerificationPage />} />
        <Route path="/verifikasi/hmj-ukm" element={<HmjUkmVerificationPage />} />
        <Route path="/verifikasi/anggota" element={<MemberVerificationPage />} />
      </Route>
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}

export default App
