import FormField from './FormField.jsx'

function TextInput({
  id,
  label,
  name,
  value,
  onChange,
  type = 'text',
  required,
  hint,
  error,
  placeholder,
  autoComplete,
  disabled,
}) {
  return (
    <FormField id={id} label={label} required={required} hint={hint} error={error}>
      <input
        id={id}
        name={name}
        type={type}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        autoComplete={autoComplete}
        disabled={disabled}
        required={required}
        className="form-input"
        aria-invalid={Boolean(error)}
        aria-describedby={error ? `${id}-error` : undefined}
      />
    </FormField>
  )
}

export default TextInput
