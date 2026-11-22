import { Link, useLocation } from 'react-router-dom'
import AdminIcon from './admin/AdminIcon.jsx'

function getContextBadge(pathname) {
  if (!pathname) return 'Platform Ormavers'
  if (pathname.startsWith('/superadmin')) return 'Panel Superadmin'
  if (pathname.startsWith('/admin')) return 'Panel Admin Kampus'
  if (pathname.startsWith('/verifikasi')) return 'Organisasi Terverifikasi'
  if (pathname.startsWith('/direktori')) return 'Direktori Resmi PTMA'
  if (pathname.startsWith('/jelajah-event') || pathname.startsWith('/event')) return 'Portal Event Ormawa'
  return 'Platform Ormavers'
}

function PageHeader({ title, subtitle, breadcrumb = [], icon, action, backTo, onBack, badge, hideBadge = false }) {
  let pathname = ''
  try {
    const location = useLocation()
    pathname = location?.pathname || ''
  } catch {
    pathname = typeof window !== 'undefined' ? window.location.pathname : ''
  }

  const badgeText = badge !== undefined ? badge : getContextBadge(pathname)

  const renderBackBtn = () => {
    if (backTo) {
      return (
        <Link to={backTo} className="page-header-back-btn">
          &larr; Kembali
        </Link>
      )
    }
    if (onBack) {
      return (
        <button type="button" onClick={onBack} className="page-header-back-btn">
          &larr; Kembali
        </button>
      )
    }
    return null
  }

  return (
    <header className="page-header-card page-header">
      <div className="page-header__body page-header__content">
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

        <div className="page-header__main">
          {icon && (
            typeof icon === 'string' ? (
              <div className="page-header__icon-badge" aria-hidden="true">
                <AdminIcon name={icon} size={22} />
              </div>
            ) : (
              icon
            )
          )}
          <div className="page-header__text">
            <h1 className="page-header__title">{title}</h1>
            {subtitle && (
              typeof subtitle === 'string' ? (
                <p className="page-header__subtitle">{subtitle}</p>
              ) : (
                <div className="page-header__subtitle">{subtitle}</div>
              )
            )}
            {(renderBackBtn() || action) && (
              <div className="page-header__action">
                {renderBackBtn()}
                {action}
              </div>
            )}
          </div>
        </div>
      </div>

      {!hideBadge && badgeText && (
        <div className="page-header__meta">
          <span className="page-header__meta-dot" aria-hidden="true" />
          <span className="page-header__meta-text">{badgeText}</span>
        </div>
      )}
    </header>
  )
}

export default PageHeader
