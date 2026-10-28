/**
 * Cache Service - localStorage-based caching untuk API responses
 * 
 * Features:
 * - Store data dengan TTL (Time To Live)
 * - Auto-expire cached data setelah TTL habis
 * - Support untuk clear individual cache atau all cache
 * - JSON serialization/deserialization otomatis
 */

const CACHE_PREFIX = 'ormavers_cache_'
const DEFAULT_TTL = 10 * 60 * 1000 // 10 menit dalam milliseconds

/**
 * Get data from cache
 * @param {string} key - Cache key
 * @param {number} ttl - Time to live dalam milliseconds (default: 10 menit)
 * @returns {any|null} - Cached data atau null jika tidak ada/expired
 */
export function getFromCache(key, ttl = DEFAULT_TTL) {
  try {
    const cacheKey = CACHE_PREFIX + key
    const cached = localStorage.getItem(cacheKey)
    
    if (!cached) {
      return null
    }

    const { data, timestamp } = JSON.parse(cached)
    const now = Date.now()
    
    // Check apakah cache sudah expired
    if (now - timestamp > ttl) {
      localStorage.removeItem(cacheKey)
      return null
    }

    return data
  } catch (error) {
    console.error('[CacheService] Error getting from cache:', error)
    return null
  }
}

/**
 * Set data to cache
 * @param {string} key - Cache key
 * @param {any} data - Data yang akan di-cache (akan di-serialize ke JSON)
 */
export function setInCache(key, data) {
  try {
    const cacheKey = CACHE_PREFIX + key
    const cacheData = {
      data,
      timestamp: Date.now()
    }
    localStorage.setItem(cacheKey, JSON.stringify(cacheData))
  } catch (error) {
    console.error('[CacheService] Error setting cache:', error)
    // Jika localStorage penuh, hapus cache lama
    if (error.name === 'QuotaExceededError') {
      clearOldCache()
      // Try again setelah clear
      try {
        const cacheKey = CACHE_PREFIX + key
        const cacheData = {
          data,
          timestamp: Date.now()
        }
        localStorage.setItem(cacheKey, JSON.stringify(cacheData))
      } catch (retryError) {
        console.error('[CacheService] Error setting cache after clear:', retryError)
      }
    }
  }
}

/**
 * Clear specific cache by key
 * @param {string} key - Cache key yang akan dihapus
 */
export function clearCache(key) {
  try {
    const cacheKey = CACHE_PREFIX + key
    localStorage.removeItem(cacheKey)
  } catch (error) {
    console.error('[CacheService] Error clearing cache:', error)
  }
}

/**
 * Clear all Ormavers cache
 */
export function clearAllCache() {
  try {
    const keys = Object.keys(localStorage)
    keys.forEach(key => {
      if (key.startsWith(CACHE_PREFIX)) {
        localStorage.removeItem(key)
      }
    })
  } catch (error) {
    console.error('[CacheService] Error clearing all cache:', error)
  }
}

/**
 * Clear cache yang sudah expired (cleanup utility)
 */
export function clearOldCache() {
  try {
    const keys = Object.keys(localStorage)
    const now = Date.now()
    
    keys.forEach(key => {
      if (key.startsWith(CACHE_PREFIX)) {
        try {
          const cached = localStorage.getItem(key)
          if (cached) {
            const { timestamp } = JSON.parse(cached)
            // Hapus jika lebih dari 30 menit (safety cleanup)
            if (now - timestamp > 30 * 60 * 1000) {
              localStorage.removeItem(key)
            }
          }
        } catch (err) {
          // Jika error parsing, hapus saja
          localStorage.removeItem(key)
        }
      }
    })
  } catch (error) {
    console.error('[CacheService] Error clearing old cache:', error)
  }
}

/**
 * Check apakah cache exist dan belum expired
 * @param {string} key - Cache key
 * @param {number} ttl - Time to live dalam milliseconds
 * @returns {boolean}
 */
export function isCacheValid(key, ttl = DEFAULT_TTL) {
  return getFromCache(key, ttl) !== null
}

// Export semua functions
export default {
  getFromCache,
  setInCache,
  clearCache,
  clearAllCache,
  clearOldCache,
  isCacheValid
}
