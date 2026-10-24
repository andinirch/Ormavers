import { NavLink } from 'react-router-dom'
import OrmaLogo from './OrmaLogo.jsx'
import AdminIcon from './admin/AdminIcon.jsx'
import LogoutButton from './LogoutButton.jsx'

function Sidebar({ groups, open = false, onClose, logoTo = '/', ariaLabel = 'Navigasi', showBrand = true, variant = '' }) {
  return (
    <>
      <aside className={`app-sidebar${variant ? ` app-sidebar--${variant}` : ''}${open ? ' is-open' : ''}`}>
        {showBrand && <OrmaLogo to={logoTo} onClick={onClose} variant="sidebar" className="app-sidebar__brand" />}
        <nav className="app-sidebar__nav" aria-label={ariaLabel}>
          {groups.map((group) => (
            <div className="app-sidebar__group" key={group.label}>
              <p>{group.label}</p>
              {group.items.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.end}
                  onClick={onClose}
                  className={({ isActive }) => {
                    const active = item.isActive ? item.isActive(isActive) : isActive
                    return `app-sidebar__link${active ? ' is-active' : ''}`
                  }}
                >
                  <AdminIcon name={item.icon} />
                  <span>{item.label}</span>
                </NavLink>
              ))}
            </div>
          ))}
        </nav>
        <LogoutButton />
      </aside>
      {open && <button className="app-sidebar__overlay" type="button" aria-label="Tutup menu" onClick={onClose} />}
    </>
  )
}

export default Sidebar
