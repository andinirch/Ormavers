import { useState } from 'react'

/**
 * ImageWithFallback - Smart image component dengan error handling
 * 
 * Features:
 * - Show loading state saat gambar loading
 * - Show fallback placeholder jika gambar error/404
 * - Support custom fallback component
 * - Handle broken image links gracefully
 * 
 * Usage:
 * <ImageWithFallback
 *   src={imageUrl}
 *   alt="Portfolio"
 *   fallback={<CustomPlaceholder />}
 * />
 */

function ImageWithFallback({ 
  src, 
  alt = 'Image', 
  fallback = null,
  className = '',
  style = {},
  ...props 
}) {
  const [imageState, setImageState] = useState('loading') // 'loading' | 'loaded' | 'error'
  const [imageSrc, setImageSrc] = useState(src)

  const handleLoad = () => {
    setImageState('loaded')
  }

  const handleError = () => {
    setImageState('error')
    console.warn(`[ImageWithFallback] Failed to load image: ${src}`)
  }

  // Update imageSrc ketika src prop berubah
  if (src !== imageSrc && imageState !== 'loading') {
    setImageSrc(src)
    setImageState('loading')
  }

  // Show fallback jika error
  if (imageState === 'error') {
    if (fallback) {
      return fallback
    }

    // Default fallback: placeholder dengan icon
    return (
      <div 
        className={`image-fallback ${className}`}
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#f3f4f6',
          color: '#9ca3af',
          borderRadius: '8px',
          ...style
        }}
      >
        <svg 
          width="48" 
          height="48" 
          viewBox="0 0 24 24" 
          fill="none" 
          stroke="currentColor" 
          strokeWidth="2"
          strokeLinecap="round" 
          strokeLinejoin="round"
        >
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
          <circle cx="8.5" cy="8.5" r="1.5" />
          <polyline points="21 15 16 10 5 21" />
        </svg>
      </div>
    )
  }

  return (
    <>
      {/* Show skeleton saat loading */}
      {imageState === 'loading' && (
        <div 
          className={`image-skeleton ${className}`}
          style={{
            backgroundColor: '#f0f0f0',
            animation: 'shimmer 1.5s infinite',
            ...style
          }}
        />
      )}

      {/* Actual image (hidden saat loading) */}
      <img
        src={imageSrc}
        alt={alt}
        className={className}
        style={{
          ...style,
          display: imageState === 'loaded' ? 'block' : 'none'
        }}
        onLoad={handleLoad}
        onError={handleError}
        {...props}
      />

      <style>{`
        @keyframes shimmer {
          0% {
            opacity: 1;
          }
          50% {
            opacity: 0.5;
          }
          100% {
            opacity: 1;
          }
        }
      `}</style>
    </>
  )
}

export default ImageWithFallback
