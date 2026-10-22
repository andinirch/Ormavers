const ADMIN_LOGO_SRC = '/images/OrmaversLogo.png'

function Logo({ variant = 'sidebar' }) {
  return <img className={`admin-logo-image admin-logo-image--${variant}`} src={ADMIN_LOGO_SRC} alt="Ormavers" />
}
export default Logo
