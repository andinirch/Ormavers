/**
 * PortfolioSkeleton - Loading skeleton untuk Portfolio items
 * Menampilkan placeholder saat portfolio data loading
 */

function PortfolioSkeleton({ count = 3 }) {
  return (
    <>
      {Array.from({ length: count }).map((_, index) => (
        <div key={index} className="portfolio-item skeleton-card" style={{ marginBottom: '20px' }}>
          {/* Thumbnail Skeleton */}
          <div className="skeleton skeleton-image" style={{ width: '120px', height: '120px', borderRadius: '10px', marginRight: '20px' }}></div>
          
          <div style={{ flex: 1 }}>
            {/* Title Skeleton */}
            <div className="skeleton skeleton-text skeleton-text--large" style={{ width: '70%', marginBottom: '8px' }}></div>
            
            {/* Year Badge Skeleton */}
            <div className="skeleton skeleton-badge" style={{ width: '80px', height: '24px', marginBottom: '12px' }}></div>
            
            {/* Description Skeleton */}
            <div className="skeleton skeleton-text" style={{ width: '100%', marginBottom: '4px' }}></div>
            <div className="skeleton skeleton-text" style={{ width: '90%', marginBottom: '4px' }}></div>
            <div className="skeleton skeleton-text" style={{ width: '60%' }}></div>
          </div>
        </div>
      ))}

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

        .skeleton-text--large {
          height: 20px;
        }

        .skeleton-image {
          background: linear-gradient(
            90deg,
            #e8e8e8 25%,
            #d8d8d8 50%,
            #e8e8e8 75%
          );
          background-size: 200% 100%;
          animation: shimmer 1.5s infinite;
        }

        .skeleton-badge {
          border-radius: 999px;
        }

        .skeleton-card {
          opacity: 0.7;
          pointer-events: none;
          display: flex;
          align-items: flex-start;
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
    </>
  )
}

export default PortfolioSkeleton
