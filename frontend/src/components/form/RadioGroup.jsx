function RadioGroup({ name, legend, value, onChange, options, required, hint, error }) {
  return (
    <fieldset
      className={error ? 'form-field form-fieldset has-error' : 'form-field form-fieldset'}
    >
      <legend className="form-field__label">
        {legend}
        {required && (
          <span className="form-field__required" aria-hidden="true">
            *
          </span>
        )}
      </legend>
      <div className="form-radio-group">
        {options.map((option) => (
          <label key={option.value} className="form-radio">
            <input
              type="radio"
              name={name}
              value={option.value}
              checked={value === option.value}
              onChange={onChange}
              disabled={option.disabled}
            />
            <span>{option.label}</span>
          </label>
        ))}
      </div>
      {hint && !error && <p className="form-field__hint">{hint}</p>}
      {error && (
        <p className="form-field__error" id={`${name}-error`} role="alert">
          {error}
        </p>
      )}
    </fieldset>
  )
}

export default RadioGroup
