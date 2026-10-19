import '../styles/platform-logo.css'

function PlatformLogo({ variant = 'auth', className = '', alt = 'Ormavers' }) {
  return (
    <img
      className={`platform-logo platform-logo--${variant} ${className}`.trim()}
      src="/images/OrmaversLogo.png"
      alt={alt}
    />
  )
}

export default PlatformLogo
