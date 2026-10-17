import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { PageIntro } from './DashboardPage.jsx'
import { useAdminData } from '../../services/useAdminData.jsx'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import '../../styles/permission-page.css'

const ROLE_LABELS = {
  super_admin: 'super admin',
  akun_hmj_ukm: 'hmj/ukm',
  akun_bem: 'bem',
}

const ROLE_VARIANTS = {
  super_admin: 'super-admin',
  akun_hmj_ukm: 'hmj-ukm',
  akun_bem: 'bem',
}

const MODULES = {
  'Pengguna & Role': ['view users', 'create users', 'edit users', 'delete users', 'view roles', 'create roles', 'edit roles', 'delete roles'],
  Verifikasi: ['verify bem', 'verify hmj/ukm', 'verify members'],
  'Profil & Keanggotaan': ['view profile', 'view registration status', 'view members'],
}

function roleLabel(name) {
  return ROLE_LABELS[name] || name.replace(/_/g, ' ')
}

function roleVariant(name) {
  return ROLE_VARIANTS[name] || name.replace(/[\s_/]+/g, '-').toLowerCase()
}

function moduleForPermission(permission) {
  return Object.entries(MODULES).find(([, permissions]) => permissions.includes(permission))?.[0] || 'Lainnya'
}

function PermissionsPage() {
  const { roles } = useAdminData()
  const [query, setQuery] = useState('')
  const [module, setModule] = useState('')
  const [appliedModule, setAppliedModule] = useState('')
  const allPermissions = useMemo(() => [...new Set(roles.flatMap((role) => role.permissions.split(',').map((permission) => permission.trim()).filter(Boolean)))], [roles])
  const permissions = useMemo(() => allPermissions.filter((permission) => {
    const matchesQuery = permission.toLowerCase().includes(query.toLowerCase())
    const matchesModule = !appliedModule || moduleForPermission(permission) === appliedModule
    return matchesQuery && matchesModule
  }), [allPermissions, appliedModule, query])

  const resetFilters = () => {
    setQuery('')
    setModule('')
    setAppliedModule('')
  }

  return <main className="admin-page admin-permission-page"><PageIntro eyebrow="AKSES & KEAMANAN • SUPER ADMIN" title="Permission" description="Daftar hak akses sistem dan role yang menggunakannya." /><section className="admin-super-admin-summary" aria-label="Tanggung jawab Super Admin"><div><strong>Tanggung Jawab Super Admin</strong><span>Kelola akun Administrator dan pantau Log Aktivitas.</span></div></section><section className="admin-filter-card admin-permission-filter"><div className="admin-input-wrap"><AdminIcon name="search" /><input placeholder="Cari permission..." value={query} onChange={(event) => setQuery(event.target.value)} /></div><div className="admin-permission-select"><select className="admin-permission-select__control" value={module} onChange={(event) => setModule(event.target.value)}><option value="">Semua modul</option>{Object.keys(MODULES).map((item) => <option key={item} value={item}>{item}</option>)}</select><span className="admin-select-chevron"><AdminIcon name="chevron-down" size={16} /></span></div><button className="admin-button admin-permission-apply" onClick={() => setAppliedModule(module)}><AdminIcon name="filter-funnel" size={16} />Terapkan</button><button className="admin-button admin-button--muted" onClick={resetFilters}><AdminIcon name="refresh" size={16} />Reset</button></section><section className="admin-panel admin-permission-panel"><div className="admin-info-banner"><p><AdminIcon name="info" size={16} />Permission bersifat tetap dari sistem. Atur penggunaannya melalui halaman <Link to="/admin/roles" className="admin-info-link">Role</Link>.</p><span>MODE BACA SAJA</span></div><div className="admin-table-wrap"><table className="admin-permission-table"><colgroup><col className="admin-permission-col-number" /><col className="admin-permission-col-name" /><col className="admin-permission-col-module" /><col /></colgroup><thead><tr><th>#</th><th>PERMISSION</th><th>MODUL</th><th>DIGUNAKAN OLEH ROLE</th></tr></thead><tbody>{permissions.map((permission, index) => { const permissionModule = moduleForPermission(permission); const previousModule = permissions[index - 1] ? moduleForPermission(permissions[index - 1]) : null; const showModuleBadge = permissionModule !== previousModule; const rolesForPermission = roles.filter((role) => role.permissions.split(',').map((item) => item.trim()).includes(permission)); return <tr key={permission}><td>{index + 1}</td><td><code className={`admin-code admin-permission-code${permissionModule === 'Verifikasi' ? ' admin-permission-code--verification' : ''}`}>{permission}</code></td><td><span className={`admin-permission-module admin-permission-module--${permissionModule === 'Verifikasi' ? 'verification' : permissionModule === 'Profil & Keanggotaan' ? 'profile' : 'default'}`}>{showModuleBadge && <span aria-hidden="true">•</span>}{permissionModule}</span></td><td><div className="admin-chip-list">{rolesForPermission.map((role) => <span className={`admin-role-badge admin-role-badge--${roleVariant(role.name)}`} key={role.id}>{roleLabel(role.name)}</span>)}</div></td></tr> })}</tbody></table></div><footer className="admin-policy-footer"><span>Menampilkan <b>{permissions.length}</b> dari <b>{allPermissions.length}</b> hak akses total</span></footer></section></main>
}

export default PermissionsPage
