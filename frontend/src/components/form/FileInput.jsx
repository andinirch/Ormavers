import FormField from './FormField.jsx'

function FileInput({
  id,
  label,
  name,
  onChange,
  accept = 'application/pdf',
  required,
  hint,
  error,
  fileName,
  disabled,
}) {
  return (
    <FormField id={id} label={label} required={required} hint={hint} error={error}>
      <input
        id={id}
        name={name}
        type="file"
        accept={accept}
        onChange={onChange}
        disabled={disabled}
        required={required}
        className="form-input form-input--file"
        aria-invalid={Boolean(error)}
        aria-describedby={error ? `${id}-error` : undefined}
      />
      <p className="form-file__name">{fileName || 'Belum ada file dipilih'}</p>
    </FormField>
  )
}

export default FileInput
