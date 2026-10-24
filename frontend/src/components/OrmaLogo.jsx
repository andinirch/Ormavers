import { Link } from 'react-router-dom'
import PlatformLogo from './PlatformLogo.jsx'
import '../styles/orma-logo.css'

function OrmaLogo({ to = '/', variant = 'auth', className = '', alt = 'Ormavers', onClick }) {
  return (
    <Link to={to} onClick={onClick} className={`orma-logo ${className}`.trim()} aria-label="Beranda Ormavers">
      <PlatformLogo variant={variant} alt={alt} />
    </Link>
  )
}

export default OrmaLogo
