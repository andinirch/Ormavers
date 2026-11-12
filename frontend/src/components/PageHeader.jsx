import { Link } from 'react-router-dom'
import AdminIcon from './admin/AdminIcon.jsx'

function PageHeader({ title, subtitle, breadcrumb = [], icon, action }) {
  return (
    <header className="page-header-card">
      {breadcrumb && breadcrumb.length > 0 && (
        <nav className="page-header__breadcrumb" aria-label="Breadcrumb">
          {breadcrumb.map((item, index) => {
            const isLast = index === breadcrumb.length - 1
            const isObject = typeof item === 'object' && item !== null
            const label = isObject ? item.label : item
            const to = isObject ? item.to : null

            return (
              <span key={index} className="page-header__breadcrumb-item">
                {!isLast && to ? (
                  <Link to={to} className="page-header__breadcrumb-link">{label}</Link>
                ) : (
                  <span className={`page-header__breadcrumb-text${isLast ? ' is-current' : ''}`}>{label}</span>
                )}
                {!isLast && <span className="page-header__breadcrumb-separator" aria-hidden="true">/</span>}
              </span>
            )
          })}
        </nav>
      )}

      <div className="page-header__content">
        <div className="page-header__main">
          {icon && (
            <div className="page-header__icon-badge" aria-hidden="true">
              {typeof icon === 'string' ? <AdminIcon name={icon} size={22} /> : icon}
            </div>
          )}
          <div className="page-header__text">
            <h1 className="page-header__title">{title}</h1>
            {subtitle && <p className="page-header__subtitle">{subtitle}</p>}
            {action && (
              <div className="page-header__action">
                {action}
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  )
}

export default PageHeader
