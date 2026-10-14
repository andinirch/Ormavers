import { useContext } from 'react'
import { AdminDataContext } from './adminData.jsx'

export function useAdminData() {
  return useContext(AdminDataContext)
}
