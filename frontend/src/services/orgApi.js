import api, { responseData } from '../api/api.js'
import { getFromCache, setInCache } from './cacheService.js'

// Cache TTLs (dalam milliseconds)
const CACHE_TTL_UNIVERSITAS = 10 * 60 * 1000 // 10 menit
const CACHE_TTL_BEM = 10 * 60 * 1000 // 10 menit
const CACHE_TTL_HMJ_UKM = 5 * 60 * 1000 // 5 menit (lebih sering update)

function normalizeOrganizationOption(option) {
  const organizationId = option.id_organisasi ?? option.organization_id ?? option.value
  return {
    ...option,
    value: organizationId,
    label: option.nama_organisasi ?? option.label,
    organization_id: organizationId,
    university_id: option.id_universitas ?? option.university_id,
    organization_type: option.jenis_organisasi ?? option.organization_type ?? option.type,
  }
}

export async function getUniversitas() {
  // Try cache first
  const cached = getFromCache('universitas', CACHE_TTL_UNIVERSITAS)
  if (cached) {
    console.log('[orgApi] Using cached universitas data')
    return cached
  }

  // Fetch dari API
  console.log('[orgApi] Fetching universitas from API')
  const result = await api.get('/universitas').then(responseData)
  const items = result?.data?.universitas ?? result?.universitas ?? []
  const data = items.map((item) => ({
    value: item.id_universitas,
    label: item.nama_universitas,
    logo: item.logo,
  }))

  // Save to cache
  setInCache('universitas', data)
  return data
}

export async function getVerifiedBem() {
  // Try cache first
  const cached = getFromCache('verified_bem', CACHE_TTL_BEM)
  if (cached) {
    console.log('[orgApi] Using cached BEM data')
    return cached
  }

  // Fetch dari API
  console.log('[orgApi] Fetching BEM from API')
  const result = await api.get('/public/bem').then(responseData)
  const items = result?.data?.bem ?? result?.bem ?? []
  const data = { data: items.map(normalizeOrganizationOption), unavailable: false }

  // Save to cache
  setInCache('verified_bem', data)
  return data
}

export async function getVerifiedHmjUkm(bemId) {
  // Try cache first
  const cacheKey = `hmj_ukm_${bemId}`
  const cached = getFromCache(cacheKey, CACHE_TTL_HMJ_UKM)
  if (cached) {
    console.log(`[orgApi] Using cached HMJ/UKM data for BEM ${bemId}`)
    return cached
  }

  // Fetch dari API
  console.log(`[orgApi] Fetching HMJ/UKM for BEM ${bemId} from API`)
  const result = await api.get(`/public/bem/${bemId}/organisasi`).then(responseData)
  const items = result?.data?.organisasi ?? result?.organisasi ?? []
  const data = { data: items.map(normalizeOrganizationOption), unavailable: false }

  // Save to cache
  setInCache(cacheKey, data)
  return data
}

export async function getOrganizationDirectory(universityId, universitiesData = null, bemData = null) {
  // Reuse data jika sudah di-pass (avoid duplicate API calls)
  const universities = universitiesData || await getUniversitas()
  const university = universities.find((item) => String(item.value) === String(universityId))
  if (!university) throw new Error('Universitas tidak ditemukan.')

  const bemResult = bemData || await getVerifiedBem()
  const bems = bemResult.data.filter((bem) => String(bem.university_id) === String(universityId))
  const results = await Promise.all(bems.map((bem) => getVerifiedHmjUkm(bem.organization_id)))
  const organizations = results.flatMap((result) => result.data).map((organization) => ({
    id: organization.organization_id,
    name: organization.label,
    type: String(organization.organization_type ?? '').toUpperCase(),
    deskripsi: organization.deskripsi ?? '', // Ready for when backend sends it
    logo: organization.logo ?? null, // Ready for when backend sends it
  }))

  return { university, organizations }
}

export async function getUniversityOrganizationCounts(universities, bemData = null) {
  // Reuse bemData jika sudah di-pass (avoid duplicate API call)
  const bemResult = bemData || await getVerifiedBem()
  const counts = new Map(universities.map((university) => [university.value, 0]))

  await Promise.all(bemResult.data.map(async (bem) => {
    const organizations = await getVerifiedHmjUkm(bem.organization_id)
    const universityId = universities.find((university) => university.value === bem.university_id)?.value
    if (universityId == null) return
    counts.set(universityId, (counts.get(universityId) ?? 0) + organizations.data.length)
  }))

  return counts
}
