import { Component } from 'react'

/**
 * ErrorBoundary - Component untuk catch JavaScript errors di React tree
 * 
 * Features:
 * - Catch semua unhandled errors dalam child components
 * - Show fallback UI dengan error message
 * - Provide "Try Again" button untuk retry
 * - Log error untuk debugging
 * 
 * Usage:
 * <ErrorBoundary>
 *   <App />
 * </ErrorBoundary>
 */
class ErrorBoundary extends Component {
  constructor(props) {
    super(props)
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null
    }
  }

  static getDerivedStateFromError(error) {
    // Update state agar next render menampilkan fallback UI
    return { hasError: true }
  }

  componentDidCatch(error, errorInfo) {
    // Log error untuk debugging
    console.error('[ErrorBoundary] Caught error:', error, errorInfo)
    
    this.setState({
      error,
      errorInfo
    })

    // Optional: Send error ke error tracking service (Sentry, etc)
    // logErrorToService(error, errorInfo)
  }

  handleReset = () => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null
    })
    
    // Reload page untuk fresh start
    window.location.reload()
  }

  handleGoHome = () => {
    window.location.href = '/'
  }

  render() {
    if (this.state.hasError) {
      // Fallback UI saat error
      return (
        <div style={styles.container}>
          <div style={styles.card}>
            <div style={styles.iconContainer}>
              <svg 
                width="64" 
                height="64" 
                viewBox="0 0 24 24" 
                fill="none" 
                stroke="#dc2626" 
                strokeWidth="2"
                strokeLinecap="round" 
                strokeLinejoin="round"
              >
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
              </svg>
            </div>
            
            <h1 style={styles.title}>Terjadi Kesalahan</h1>
            
            <p style={styles.message}>
              Maaf, terjadi kesalahan yang tidak terduga. Silakan coba lagi atau kembali ke halaman utama.
            </p>

            {/* Show error detail di development mode */}
            {import.meta.env.DEV && this.state.error && (
              <details style={styles.details}>
                <summary style={styles.summary}>Detail Error (Development Only)</summary>
                <pre style={styles.errorText}>
                  {this.state.error.toString()}
                  {this.state.errorInfo && this.state.errorInfo.componentStack}
                </pre>
              </details>
            )}

            <div style={styles.actions}>
              <button 
                onClick={this.handleReset} 
                style={styles.primaryButton}
              >
                Coba Lagi
              </button>
              
              <button 
                onClick={this.handleGoHome} 
                style={styles.secondaryButton}
              >
                Kembali ke Beranda
              </button>
            </div>
          </div>
        </div>
      )
    }

    return this.props.children
  }
}

// Inline styles untuk ErrorBoundary (agar tidak depend pada external CSS)
const styles = {
  container: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: '100vh',
    backgroundColor: '#f9fafb',
    padding: '20px',
    fontFamily: 'system-ui, -apple-system, "Segoe UI", sans-serif'
  },
  card: {
    maxWidth: '500px',
    width: '100%',
    backgroundColor: '#ffffff',
    borderRadius: '12px',
    padding: '40px 30px',
    boxShadow: '0 10px 25px rgba(0, 0, 0, 0.1)',
    textAlign: 'center'
  },
  iconContainer: {
    marginBottom: '20px'
  },
  title: {
    fontSize: '24px',
    fontWeight: '700',
    color: '#111827',
    margin: '0 0 12px 0'
  },
  message: {
    fontSize: '16px',
    color: '#6b7280',
    lineHeight: '1.6',
    margin: '0 0 24px 0'
  },
  details: {
    marginBottom: '24px',
    textAlign: 'left',
    backgroundColor: '#fef2f2',
    border: '1px solid #fecaca',
    borderRadius: '8px',
    padding: '12px'
  },
  summary: {
    cursor: 'pointer',
    fontWeight: '600',
    color: '#dc2626',
    fontSize: '14px',
    marginBottom: '8px'
  },
  errorText: {
    fontSize: '12px',
    color: '#991b1b',
    overflow: 'auto',
    maxHeight: '200px',
    margin: '8px 0 0 0',
    padding: '8px',
    backgroundColor: '#fff',
    borderRadius: '4px'
  },
  actions: {
    display: 'flex',
    gap: '12px',
    justifyContent: 'center',
    flexWrap: 'wrap'
  },
  primaryButton: {
    padding: '12px 24px',
    backgroundColor: '#2563eb',
    color: '#ffffff',
    border: 'none',
    borderRadius: '8px',
    fontSize: '16px',
    fontWeight: '600',
    cursor: 'pointer',
    transition: 'background-color 0.2s'
  },
  secondaryButton: {
    padding: '12px 24px',
    backgroundColor: '#ffffff',
    color: '#2563eb',
    border: '2px solid #2563eb',
    borderRadius: '8px',
    fontSize: '16px',
    fontWeight: '600',
    cursor: 'pointer',
    transition: 'all 0.2s'
  }
}

export default ErrorBoundary
