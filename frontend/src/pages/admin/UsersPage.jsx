import { useState } from 'react'
import { PageIntro } from './DashboardPage.jsx'
import { useAdminData } from '../../services/useAdminData.jsx'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import { UserForm } from '../../components/adminForms.jsx'
import '../../styles/users-page.css'

const ROLE_PRESENTATION = {
  super_admin: { label: 'Super Admin', variant: 'super-admin' },
  administrator: { label: 'Administrator', variant: 'administrator' },
  bem: { label: 'BEM (Badan Eksekutif Mahasiswa)', variant: 'bem' },
  akun_bem: { label: 'BEM (Badan Eksekutif Mahasiswa)', variant: 'bem' },
  akun_hmj_ukm: { label: 'HMJ / UKM', variant: 'hmj-ukm' },
  anggota: { label: 'Anggota', variant: 'anggota' },
}

const STATUS_LABELS = {
   aktif: 'Aktif',
   nonaktif: 'Nonaktif',
   menunggu: 'Menunggu',
   ditolak: 'Ditolak',
   ditangguhkan: 'Ditangguhkan',
 }

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']

function avatarTone(name) {
  const score = [...(name || '')].reduce((total, character) => total + character.charCodeAt(0), 0)
  return (score % 5) + 1
}

function formatLogin(value) {
  if (!value) return ''
  const [date, time] = value.replace('T', ' ').split(' ')
  const [year, month, day] = (date || '').split('-')
  if (!year || !month || !day) return value
  return `${Number(day)} ${MONTHS[Number(month) - 1] || month} ${year}\n${(time || '').slice(0, 5)} WIB`
}

function UsersPage() {
   const { users, usersMeta, usersSummary, roles, refreshUsers, updateUser } = useAdminData()
   const [query, setQuery] = useState('')
   const [role, setRole] = useState('')
   const [status, setStatus] = useState('')
   const [perPage] = useState(8)
   const [creating, setCreating] = useState(false)
   const [actionError, setActionError] = useState('')
   const [deactivatingId, setDeactivatingId] = useState(null)
   const [loadingFilter, setLoadingFilter] = useState(false)

   const currentPage = usersMeta.current_page ?? 1
   const totalPages = usersMeta.last_page ?? 1
   const visibleUsers = users
   const firstIndex = users.length ? (currentPage - 1) * perPage + 1 : 0
   const lastIndex = Math.min(currentPage * perPage, usersMeta.total ?? 0)

   const getRole = (r) => r || '—'
   const getOrg = (name) => name || '—'

   const onReset = async () => {
     setQuery('')
     setRole('')
     setStatus('')
     setActionError('')
     setLoadingFilter(true)
     try {
       await refreshUsers({ page: 1, per_page: perPage })
     } finally {
       setLoadingFilter(false)
     }
   }

   const onApply = async () => {
     setActionError('')
     setLoadingFilter(true)
     try {
       const params = { page: 1, per_page: perPage }
       if (query.trim()) params.q = query.trim()
       if (role) params.role = role
       if (status) params.status = status
       await refreshUsers(params)
     } finally {
       setLoadingFilter(false)
     }
   }

  const handleStatusChange = async (user) => {
    if (deactivatingId) return
    const nextStatus = user.status === 'aktif' ? 'nonaktif' : 'aktif'
    const actionLabel = nextStatus === 'aktif' ? 'mengaktifkan' : 'menonaktifkan'
    const confirmed = window.confirm(`${actionLabel[0].toUpperCase()}${actionLabel.slice(1)} akun Administrator ${user.name}?`)
    if (!confirmed) return
    setActionError('')
    setDeactivatingId(user.id)
    try {
      await updateUser(user.id, nextStatus)
    } catch (err) {
      const message = err?.response?.data?.message || err?.message || `Gagal ${actionLabel} akun Administrator ${user.name}.`
      setActionError(message)
    } finally {
      setDeactivatingId(null)
    }
  }

   const goToPage = async (nextPage) => {
     const safeNextPage = Math.min(Math.max(nextPage, 1), totalPages)
     setLoadingFilter(true)
     try {
       const params = { page: safeNextPage, per_page: perPage }
       if (query.trim()) params.q = query.trim()
       if (role) params.role = role
       if (status) params.status = status
       await refreshUsers(params)
     } finally {
       setLoadingFilter(false)
     }
   }

  return (
    <main className="admin-page admin-users-page">
       <PageIntro
         eyebrow="AKSES PENGGUNA • SUPER ADMIN"
         title="Manajemen Pengguna"
         description="Kelola semua akun pengguna di platform Ormavers."
         actions={<button className="admin-button" onClick={() => setCreating(true)}><AdminIcon name="plus" size={16} />Tambah Administrator</button>}
       />
       {actionError && <p className="admin-form-error" style={{ margin: '0 0 1rem' }}>{actionError}</p>}
       <div className="admin-summary-grid admin-summary-grid--four">
         <Stat label="TOTAL TERDAFTAR" value={usersSummary.total_terdaftar ?? 0} />
         <Stat label="AKUN AKTIF" value={usersSummary.akun_aktif ?? 0} tone="teal" />
         <Stat label="VERIFIKASI TERTUNDA" value={usersSummary.verifikasi_tertunda ?? 0} tone="purple" />
         <Stat label="DITANGGUHKAN" value={(usersSummary.ditangguhkan ?? 0) + (usersSummary.nonaktif_ditolak ?? 0)} tone="red" />
       </div>
      <section className="admin-filter-card admin-users-filter">
        <div className="admin-input-wrap">
          <AdminIcon name="search" />
           <input
             placeholder="Nama atau email pengguna..."
             value={query}
             onChange={(event) => {
               setQuery(event.target.value)
             }}
           />
        </div>
        <div className="admin-users-select">
           <select
             className="admin-users-select__control"
             value={role}
             onChange={(event) => {
               setRole(event.target.value)
             }}
           >
            <option value="">Role: Semua</option>
            {roles.map((item) => (
              <option key={item.name} value={item.name}>
                {ROLE_PRESENTATION[item.name]?.label || item.name}
              </option>
            ))}
          </select>
          <span className="admin-select-chevron">⌄</span>
        </div>
        <div className="admin-users-select">
           <select
             className="admin-users-select__control"
             value={status}
             onChange={(event) => {
               setStatus(event.target.value)
             }}
           >
            <option value="">Status: Semua</option>
             {['aktif', 'nonaktif', 'menunggu', 'ditolak', 'ditangguhkan'].map((item) => (
               <option key={item} value={item}>
                 {STATUS_LABELS[item]}
               </option>
             ))}
          </select>
          <span className="admin-select-chevron">⌄</span>
        </div>
        <div className="admin-filter-actions">
          <button className="admin-button admin-button--muted" onClick={onReset}>
            ↻ Reset
          </button>
          <button className="admin-button admin-users-apply" onClick={onApply}>
            <AdminIcon name="filter-funnel" size={16} />
            Terapkan
          </button>
        </div>
      </section>
       <section className="admin-panel admin-user-panel">
         <div className="admin-panel-heading">
           <div>
             <h2>Daftar Pengguna</h2>
             <p>Seluruh akun yang terdaftar di sistem tata kelola perguruan tinggi. {loadingFilter && <small>(Memperbarui...)</small>}</p>
           </div>
         </div>
        <div className="admin-table-wrap">
          <table className="admin-user-table">
            <thead>
              <tr>
                <th>PENGGUNA</th>
                <th>ROLE</th>
                <th>ORGANISASI</th>
                <th>STATUS</th>
                <th>LOGIN TERAKHIR</th>
                <th>AKSI</th>
              </tr>
            </thead>
            <tbody>
              {visibleUsers.length === 0 ? (
                <tr>
                  <td colSpan="6" style={{ textAlign: 'center', padding: '2.5rem' }}>
                    <div className="admin-empty">
                      <strong>Tidak ada pengguna ditemukan</strong>
                      <span>Coba sesuaikan kata kunci pencarian atau filter yang dipilih.</span>
                    </div>
                  </td>
                </tr>
              ) : (
               visibleUsers.map((user) => (
                    <tr key={user.id}>
                      <td>
                        <div className="admin-user-cell">
                          <span className={`admin-avatar admin-avatar--tone-${avatarTone(user.name)}`}>
                            {user.initials}
                          </span>
                          <span>
                            <strong>{user.name}</strong>
                            <code>{user.email}</code>
                          </span>
                        </div>
                      </td>
                       <td>
                         <RoleBadge role={getRole(user.role)} />
                       </td>
                       <td>{getOrg(user.organizationName)}</td>
                      <td>
                        <Badge tone={user.status}>{STATUS_LABELS[user.status] || user.status}</Badge>
                      </td>
                      <td>
                        <code className={`admin-user-last-login${user.lastLoginAt ? '' : ' is-empty'}`}>
                          {formatLogin(user.lastLoginAt) || '— Belum pernah login'}
                        </code>
                      </td>
                        <td>
                          <div className="admin-icon-actions">
                             {user.role === 'administrator' && user.status !== 'menunggu' && (
                               <button
                                 className="admin-user-delete-action"
                                 aria-label={user.status === 'aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun'}
                                 title={user.status === 'aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun'}
                                 disabled={deactivatingId === user.id}
                                 onClick={() => handleStatusChange(user)}
                               >
                                 <AdminIcon name={user.status === 'aktif' ? 'user-x' : 'user-check'} />
                               </button>
                             )}
                          </div>
                        </td>
                     </tr>
                 ))
              )}
            </tbody>
          </table>
        </div>
         <div className="admin-pagination">
           <span>
             Menampilkan <b>{firstIndex}-{lastIndex}</b> dari <b>{usersMeta.total ?? 0}</b> pengguna
           </span>
          <div>
            <button disabled={currentPage <= 1} onClick={() => goToPage(currentPage - 1)}>
              ‹ Sebelumnya
            </button>
            {Array.from({ length: totalPages }, (_, i) => i + 1)
              .filter((p) => p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1)
              .map((p, idx, arr) => {
                const prev = arr[idx - 1]
                const showEllipsis = prev && p - prev > 1
                return (
                  <span key={p} style={{ display: 'inline-flex', alignItems: 'center' }}>
                    {showEllipsis && <span style={{ padding: '0 0.35rem' }}>…</span>}
                    <button
                      className={currentPage === p ? 'is-current' : ''}
                      onClick={() => goToPage(p)}
                    >
                      {p}
                    </button>
                  </span>
                )
              })}
            <button disabled={currentPage >= totalPages} onClick={() => goToPage(currentPage + 1)}>
              Berikutnya ›
            </button>
          </div>
        </div>
      </section>
       {creating && <UserForm onClose={() => setCreating(false)} />}
     </main>
  )
}
function Stat({ label, value, tone = 'blue' }) { return <article className={`admin-stat-card admin-stat-card--${tone}`}><span>{label}</span><strong>{value}</strong></article> }
function RoleBadge({ role }) { const presentation = ROLE_PRESENTATION[role] || { label: role, variant: 'unknown' }; return <span className={`admin-role-badge admin-role-badge--${presentation.variant}`}><span aria-hidden="true">●</span>{presentation.label}</span> }
function Badge({ tone, children }) { const normalized = tone.toLowerCase().replace(/[^a-z]/g, '-'); return <span className={`admin-badge admin-badge--${normalized}`}>● {children}</span> }
export default UsersPage
