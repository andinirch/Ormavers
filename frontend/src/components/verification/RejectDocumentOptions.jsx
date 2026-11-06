export const REJECTABLE_DOCUMENTS = [
  { value: 'foto_ktm', label: 'KTM' },
  { value: 'sk_organisasi', label: 'SK Organisasi' },
  { value: 'sk_kepengurusan', label: 'SK Kepengurusan' },
]

export default function RejectDocumentOptions({ value = [], onChange, error = '', disabled = false }) {
  const toggle = (key) => {
    onChange(value.includes(key) ? value.filter((item) => item !== key) : [...value, key])
  }

  return (
    <div className="verification-doc-options">
      <span className="verification-doc-options__title">Dokumen yang perlu diperbaiki</span>
      <div className="verification-doc-options__list">
        {REJECTABLE_DOCUMENTS.map((doc) => {
          const checked = value.includes(doc.value)
          return (
            <label key={doc.value} className={`verification-doc-option${checked ? ' is-checked' : ''}`}>
              <input
                type="checkbox"
                checked={checked}
                onChange={() => toggle(doc.value)}
                disabled={disabled}
              />
              <span>{doc.label}</span>
            </label>
          )
        })}
      </div>
      {error && <div className="verification-error">{error}</div>}
    </div>
  )
}
