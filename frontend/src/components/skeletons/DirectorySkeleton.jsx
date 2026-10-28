/**
 * DirectorySkeleton - Loading skeleton untuk DirectoryPage
 * Menampilkan placeholder dengan shimmer animation saat data loading
 */

function DirectorySkeleton() {
  return (
    <div className="directory-main">
      {/* Heading Skeleton */}
      <div className="directory-heading">
        <div className="skeleton skeleton-text skeleton-text--small" style={{ width: '150px', marginBottom: '10px' }}></div>
        <div className="skeleton skeleton-text skeleton-text--large" style={{ width: '400px', marginBottom: '8px' }}></div>
        <div className="skeleton skeleton-text" style={{ width: '500px' }}></div>
      </div>

      {/* University Grid Skeleton */}
      <div className="directory-university-grid">
        {[1, 2, 3, 4, 5, 6].map((index) => (
          <div key={index} className="directory-university-card skeleton-card">
            {/* Logo Circle */}
            <div className="skeleton skeleton-circle" style={{ width: '88px', height: '88px', margin: '0 auto 20px' }}></div>
            
            {/* Title */}
            <div className="skeleton skeleton-text skeleton-text--large" style={{ width: '80%', margin: '0 auto 8px' }}></div>
            
            {/* Subtitle */}
            <div className="skeleton skeleton-text skeleton-text--small" style={{ width: '60%', margin: '0 auto 22px' }}></div>
            
            {/* Count Badge */}
            <div className="skeleton skeleton-badge" style={{ width: '100px', margin: '0 auto' }}></div>
          </div>
        ))}
      </div>

      <style>{`
        .skeleton {
          background: linear-gradient(
            90deg,
            #f0f0f0 25%,
            #e0e0e0 50%,
          #f0f0f0 75%
          );
          background-size: 200% 100%;
          animation: shimmer 1.5s infinite;
          border-radius: 4px;
        }

        .skeleton-text {
          height: 16px;
          border-radius: 4px;
        }

        .skeleton-text--small {
          height: 12px;
        }

        .skeleton-text--large {
          height: 20px;
        }

        .skeleton-circle {
          border-radius: 50%;
        }

        .skeleton-badge {
          height: 32px;
          border-radius: 999px;
        }

        .skeleton-card {
          pointer-events: none;
          opacity: 0.7;
        }

        @keyframes shimmer {
          0% {
            background-position: -200% 0;
          }
          100% {
            background-position: 200% 0;
          }
        }
      `}</style>
    </div>
  )
}

export default DirectorySkeleton
