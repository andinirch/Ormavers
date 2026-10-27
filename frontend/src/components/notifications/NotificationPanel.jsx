import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import AdminIcon from '../admin/AdminIcon.jsx'
import '../../styles/notification-panel.css'

const FOCUSABLE = 'button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'

function NotificationItem({ icon, title, text, time, read }) {
  return <article className={`notification-item${read ? '' : ' is-unread'}`}>
    <span className="notification-item__icon"><AdminIcon name={icon} size={18} /></span>
    <div className="notification-item__body">
      <h3>{title}</h3>
      <p className="notification-item__text">{text}</p>
      <span className="notification-item__time"><AdminIcon name={read ? 'check-double' : 'clock'} size={12} /> {time}</span>
    </div>
    {!read && <span className="notification-item__dot" aria-label="Belum dibaca" />}
  </article>
}

function NotificationSection({ label, aside, items }) {
  if (!items.length) return null
  return <section>
    <header className="notification-panel-section"><span>{label}</span>{aside && <span>{aside}</span>}</header>
    <div className="notification-panel-items">{items.map((item) => <NotificationItem key={item.id} {...item} />)}</div>
  </section>
}

function NotificationPanel({ open, onClose, items, unread, onReadAll, returnFocusRef }) {
  const [tab, setTab] = useState('all')
  const panelRef = useRef(null)
  const closeRef = useRef(null)
  const onCloseRef = useRef(onClose)

  useEffect(() => { onCloseRef.current = onClose })

  useEffect(() => {
    if (!open) return undefined
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    closeRef.current?.focus()
    const returnTarget = returnFocusRef?.current
    const onKeyDown = (event) => {
      if (event.key === 'Escape') { onCloseRef.current(); return }
      if (event.key !== 'Tab' || !panelRef.current) return
      const nodes = [...panelRef.current.querySelectorAll(FOCUSABLE)]
      if (!nodes.length) return
      const first = nodes[0]
      const last = nodes[nodes.length - 1]
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
    }
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('keydown', onKeyDown)
      document.body.style.overflow = previousOverflow
      returnTarget?.focus()
    }
  }, [open, returnFocusRef])

  const visible = tab === 'unread' ? items.filter((item) => !item.read) : items
  const today = visible.filter((item) => item.group === 'today')
  const earlier = visible.filter((item) => item.group === 'earlier')

  return createPortal(<>
    <div className={`notification-panel-overlay${open ? ' is-open' : ''}`} onClick={onClose} aria-hidden="true" />
    <aside ref={panelRef} className={`notification-panel${open ? ' is-open' : ''}`} role="dialog" aria-modal="true" aria-label="Notifikasi" aria-hidden={!open}>
      <header className="notification-panel-header">
        <h2>Notifikasi {unread > 0 && <span className="notification-panel-count">{unread}</span>}</h2>
        <button ref={closeRef} type="button" className="notification-panel-close" onClick={onClose} aria-label="Tutup notifikasi"><AdminIcon name="close" size={18} /></button>
      </header>
      <div className="notification-panel-filter">
        <div className="notification-panel-tabs">
          <button type="button" className={tab === 'all' ? 'is-active' : ''} onClick={() => setTab('all')}>Semua</button>
          <button type="button" className={tab === 'unread' ? 'is-active' : ''} onClick={() => setTab('unread')}>Belum dibaca</button>
        </div>
        <button type="button" className="notification-panel-readall" onClick={onReadAll} disabled={unread === 0}>Tandai semua dibaca</button>
      </div>
      <div className="notification-panel-list">
        <NotificationSection label="HARI INI" aside={today.length ? `${today.length} pesan terbaru` : ''} items={today} />
        <NotificationSection label="SEBELUMNYA" items={earlier} />
        {!visible.length && <p className="notification-panel-empty">Tidak ada notifikasi.</p>}
      </div>
    </aside>
  </>, document.body)
}

export default NotificationPanel
