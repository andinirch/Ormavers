import axios from 'axios'

const TOKEN_KEY = 'ormavers_token'

export const getToken = () => localStorage.getItem(TOKEN_KEY)
export const setToken = (token) => localStorage.setItem(TOKEN_KEY, token)
export const clearToken = () => localStorage.removeItem(TOKEN_KEY)

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && getToken()) {
      clearToken()
      window.dispatchEvent(new Event('ormavers:unauthorized'))
    }
    return Promise.reject(error)
  },
)

export const responseData = (response) => response.data

export function assetUrl(value) {
  if (!value) return ''
  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  const origin = apiUrl.replace(/\/api\/?$/, '')
  if (/^(blob:|data:)/i.test(value)) return value
  if (/^https?:/i.test(value)) {
    try {
      const parsed = new URL(value)
      if (['localhost', '127.0.0.1'].includes(parsed.hostname)) {
        return `${origin}${parsed.pathname}${parsed.search}${parsed.hash}`
      }
    } catch {
      return value
    }
    return value
  }
  return `${origin}/${String(value).replace(/^\//, '')}`
}

export default api
