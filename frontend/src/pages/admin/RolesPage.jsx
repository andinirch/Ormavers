import { useState } from 'react'
import { RoleForm } from '../../components/adminForms.jsx'
import { useAdminData } from '../../services/useAdminData.jsx'

function RolesPage() {
  const { roles, deleteRole } = useAdminData()
  const [editing, setEditing] = useState(null)
  const [creating, setCreating] = useState(false)
  return <><section className="admin-page-heading"><h1>Roles</h1><button className="admin-button" onClick={() => setCreating(true)}>Create</button></section><main className="admin-content"><div className="admin-table-wrap"><table><thead><tr><th>#</th><th>Name</th><th>Permission</th><th>Created</th><th>Action</th></tr></thead><tbody>{roles.length ? roles.map((role, index) => <tr key={role.id}><td>{roles.length - index - 1}</td><td>{role.name}</td><td>{role.permissions}</td><td>{formatDate(role.createdAt)}</td><td>{!role.isProtected && <div className="admin-actions"><button className="admin-button admin-button--small" onClick={() => setEditing(role)}>Edit</button><button className="admin-button admin-button--small admin-button--outline" onClick={() => window.confirm(`Hapus role ${role.name}?`) && deleteRole(role.id)}>Delete</button></div>}</td></tr>) : <tr><td colSpan="5" className="admin-empty">Belum ada role.</td></tr>}</tbody></table></div></main>{(creating || editing) && <RoleForm item={editing} onClose={() => { setCreating(false); setEditing(null) }} />}</>
}
function formatDate(value) { return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) }
export default RolesPage
