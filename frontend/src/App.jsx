import { Route, Routes } from 'react-router-dom'
import AppShell from './layouts/AppShell.jsx'
import RegistrationStatusPage from './pages/RegistrationStatusPage.jsx'
import NotFoundPage from './pages/NotFoundPage.jsx'

function App() {
  return (
    <Routes>
      <Route element={<AppShell />}>
        <Route path="/" element={<RegistrationStatusPage />} />
        <Route path="/status-registrasi" element={<RegistrationStatusPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  )
}

export default App
