function FormField({ id, label, required, hint, error, children }) {
  return (
    <div className={error ? 'form-field has-error' : 'form-field'}>
      <label className="form-field__label" htmlFor={id}>
        {label}
        {required && (
          <span className="form-field__required" aria-hidden="true">
            *
          </span>
        )}
      </label>
      {children}
      {hint && !error && <p className="form-field__hint">{hint}</p>}
      {error && (
        <p className="form-field__error" id={`${id}-error`} role="alert">
          {error}
        </p>
      )}
    </div>
  )
}

export default FormField
