import '../styles/platform-logo.css'
import logo from '../assets/OrmaversLogo.png'

export default function PlatformLogo({ variant = 'auth', className = '', alt = 'Ormavers' }) {
  return <img className={`platform-logo platform-logo--${variant} ${className}`.trim()} src={logo} alt={alt} />
}
