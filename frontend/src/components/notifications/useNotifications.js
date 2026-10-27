import { useState } from 'react'
import { initialNotifications } from './notificationData.js'

export default function useNotifications() {
  const [items, setItems] = useState(initialNotifications)
  const unread = items.filter((item) => !item.read).length
  const markAllRead = () => setItems((current) => current.map((item) => ({ ...item, read: true })))
  return { items, unread, markAllRead }
}
