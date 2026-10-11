function SubmitButton({ loading, loadingText = 'Mengirim…', disabled, children }) {
  return (
    <button
      type="submit"
      className="form-submit"
      disabled={disabled || loading}
      aria-busy={loading || undefined}
    >
      {loading && <span className="form-submit__spinner" aria-hidden="true" />}
      {loading ? loadingText : children}
    </button>
  )
}

export default SubmitButton
