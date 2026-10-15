import FormField from './FormField.jsx'

function SelectInput({
  id,
  label,
  name,
  value,
  onChange,
  options,
  required,
  hint,
  error,
  placeholder,
  disabled,
}) {
  return (
    <FormField id={id} label={label} required={required} hint={hint} error={error}>
      <select
        id={id}
        name={name}
        value={value}
        onChange={onChange}
        disabled={disabled}
        required={required}
        className="form-input"
        aria-invalid={Boolean(error)}
        aria-describedby={error ? `${id}-error` : undefined}
      >
        {placeholder !== undefined && <option value="">{placeholder}</option>}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </FormField>
  )
}

export default SelectInput
