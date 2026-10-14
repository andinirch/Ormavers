import { useContext } from 'react'
import { AdminDataContext } from './AdminDataContext.jsx'

export function useAdminData() {
  return useContext(AdminDataContext)
}
