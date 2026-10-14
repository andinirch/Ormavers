import { useState } from 'react'
import { UserForm } from '../../components/adminForms.jsx'
import { useAdminData } from '../../services/useAdminData.jsx'

function UsersPage() {
  const { users, roles, organizations, deleteUser } = useAdminData()
  const [editing, setEditing] = useState(null)
  const [creating, setCreating] = useState(false)
  const roleName = (id) => roles.find((role) => role.id === id)?.name || '-'
  const orgName = (id) => organizations.find((organization) => organization.id === id)?.name || '-'
  return <><Header title="Pengguna" onCreate={() => setCreating(true)} /><main className="admin-content"><div className="admin-table-wrap"><table><thead><tr><th>#</th><th>Name</th><th>Role</th><th>Organisasi</th><th>Created</th><th>Action</th></tr></thead><tbody>{users.length ? users.map((user, index) => <tr key={user.id}><td>{users.length - index}</td><td>{user.name}</td><td>{roleName(user.roleId)}</td><td>{orgName(user.organizationId)}</td><td>{formatDate(user.createdAt)}</td><td><div className="admin-actions"><button className="admin-button admin-button--small" onClick={() => setEditing(user)}>Edit</button><button className="admin-button admin-button--small admin-button--outline" onClick={() => window.confirm(`Hapus ${user.name}?`) && deleteUser(user.id)}>Delete</button></div></td></tr>) : <tr><td colSpan="6" className="admin-empty">Belum ada pengguna.</td></tr>}</tbody></table></div></main>{(creating || editing) && <UserForm item={editing} onClose={() => { setCreating(false); setEditing(null) }} />}</>
}
function Header({ title, onCreate }) { return <section className="admin-page-heading"><h1>{title}</h1><button className="admin-button" onClick={onCreate}>Create</button></section> }
function formatDate(value) { return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) }
export default UsersPage
