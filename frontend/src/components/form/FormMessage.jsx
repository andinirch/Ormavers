function FormMessage({ variant = 'info', children }) {
  const role = variant === 'error' ? 'alert' : 'status'
  return (
    <p className={`form-message form-message--${variant}`} role={role}>
      {children}
    </p>
  )
}

export default FormMessage
