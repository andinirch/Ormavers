import { useState } from 'react'
import { AdminDataContext } from './AdminDataContext.jsx'

const initialRoles = [
  { id: 'role-anggota', name: 'anggota', permissions: 'view profile, view registration status', createdAt: '2026-10-10', isProtected: false },
  { id: 'role-hmj', name: 'hmj/ukm', permissions: 'view members, verify members, view profile', createdAt: '2026-10-09', isProtected: false },
  { id: 'role-bem', name: 'bem', permissions: 'view users, verify hmj/ukm, verify members, view profile', createdAt: '2026-10-08', isProtected: false },
  { id: 'role-administrator', name: 'administrator', permissions: 'view users, create users, edit users, delete users, view roles, verify bem, verify hmj/ukm, verify members', createdAt: '2026-10-07', isProtected: false },
  { id: 'role-super-admin', name: 'super admin', permissions: '( akses penuh, tanpa daftar permission )', createdAt: '2026-10-06', isProtected: true },
]

const initialOrganizations = [
  { id: 'org-bem', name: 'Nama BEM' },
  { id: 'org-hmj', name: 'Nama HMJ/UKM' },
]

const initialUsers = [
  { id: 'user-001', name: 'Nama pengguna', roleId: 'role-anggota', organizationId: 'org-hmj', createdAt: '2026-10-10' },
  { id: 'user-002', name: 'Nama pengguna', roleId: 'role-hmj', organizationId: 'org-hmj', createdAt: '2026-10-09' },
  { id: 'user-003', name: 'Nama pengguna', roleId: 'role-bem', organizationId: 'org-bem', createdAt: '2026-10-08' },
]

export function AdminDataProvider({ children }) {
  const [users, setUsers] = useState(initialUsers)
  const [roles, setRoles] = useState(initialRoles)
  const [organizations] = useState(initialOrganizations)

  const value = {
    users,
    roles,
    organizations,
    createUser: (input) => setUsers((items) => [...items, { ...input, id: `user-${Date.now()}`, createdAt: new Date().toISOString().slice(0, 10) }]),
    updateUser: (id, input) => setUsers((items) => items.map((item) => item.id === id ? { ...item, ...input } : item)),
    deleteUser: (id) => setUsers((items) => items.filter((item) => item.id !== id)),
    createRole: (input) => setRoles((items) => [...items, { ...input, id: `role-${Date.now()}`, createdAt: new Date().toISOString().slice(0, 10), isProtected: false }]),
    updateRole: (id, input) => setRoles((items) => items.map((item) => item.id === id ? { ...item, ...input } : item)),
    deleteRole: (id) => setRoles((items) => items.filter((item) => item.id !== id)),
  }

  return <AdminDataContext.Provider value={value}>{children}</AdminDataContext.Provider>
}

