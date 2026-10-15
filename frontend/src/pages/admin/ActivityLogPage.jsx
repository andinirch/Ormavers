import { PageIntro } from './DashboardPage.jsx'

function ActivityLogPage() {
  return <main className="admin-page"><PageIntro eyebrow="AUDIT TRAIL & KEAMANAN • SUPER ADMIN" title="Log Aktivitas" description="Riwayat seluruh aktivitas sistem, perubahan akun, role, dan permission." /><section className="admin-panel admin-availability-panel"><div className="admin-empty"><strong>Log aktivitas belum tersedia</strong><span>Integrasi activity log belum tersedia pada frontend ini. Data tidak dibuat secara lokal agar tidak menampilkan audit trail yang tidak bersumber dari sistem.</span></div></section></main>
}
export default ActivityLogPage
