import { Navigate, Route, Routes } from 'react-router-dom'
import NotFoundPage from './pages/NotFoundPage.jsx'
import VerificationShell from './layouts/VerificationShell.jsx'
import BemVerificationPage from './pages/BemVerificationPage.jsx'
import BemOrganizationVerificationPage from './pages/BemOrganizationVerificationPage.jsx'
import HmjUkmVerificationPage from './pages/HmjUkmVerificationPage.jsx'

function App() {
  return (
    <Routes>
      <Route element={<VerificationShell />}>
        <Route index element={<Navigate to="/verifikasi/administrator" replace />} />
        <Route path="/verifikasi/administrator" element={<BemVerificationPage />} />
        <Route path="/verifikasi/bem" element={<BemOrganizationVerificationPage />} />
        <Route path="/verifikasi/hmj-ukm" element={<HmjUkmVerificationPage />} />
      </Route>
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}

export default App
