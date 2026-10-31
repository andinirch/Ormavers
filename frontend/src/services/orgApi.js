import api, { assetUrl, responseData } from '../api/api.js'
import { getFromCache, setInCache } from './cacheService.js'

// Cache TTLs (dalam milliseconds)
const CACHE_TTL_UNIVERSITAS = 10 * 60 * 1000 // 10 menit
const CACHE_TTL_BEM = 10 * 60 * 1000 // 10 menit
const CACHE_TTL_HMJ_UKM = 5 * 60 * 1000 // 5 menit (lebih sering update)
const CACHE_TTL_DIRECTORY_COUNTS = 5 * 60 * 1000

function normalizeOrganizationOption(option) {
  const organizationId = option.id_organisasi ?? option.organization_id ?? option.value
  return {
    ...option,
    id: organizationId,
    value: organizationId,
    label: option.nama_organisasi ?? option.label,
    name: option.nama_organisasi ?? option.label,
    organization_id: organizationId,
    university_id: option.id_universitas ?? option.university_id,
    field_id: option.id_bidang ?? option.field_id,
    field_name: option.nama_bidang ?? option.field_name,
    logo: assetUrl(option.logo),
    organization_type: String(option.jenis_organisasi ?? option.organization_type ?? option.type ?? '').toUpperCase(),
  }
}

export async function getDirectoryUniversities() {
  const cached = getFromCache('directory_universities', CACHE_TTL_UNIVERSITAS)
  if (cached) return cached

  const result = await api.get('/direktori/universitas').then(responseData)
  const items = Array.isArray(result?.data) ? result.data : result?.data?.universitas ?? []
  const data = items.map((item) => ({
    value: item.id_universitas,
    label: item.nama_universitas,
    logo: item.logo ? `${assetUrl(item.logo)}${item.logo.includes('?') ? '&' : '?'}v=${Date.now()}` : '',
    isDefaultLogo: item.is_default_logo,
  }))
  setInCache('directory_universities', data)
  return data
}

export async function getDirectoryOrganizations(universityId) {
  const result = await api.get(`/direktori/universitas/${universityId}/organisasi`).then(responseData)
  const payload = result?.data ?? {}
  return {
    university: payload.universitas ? {
      value: payload.universitas.id_universitas,
      label: payload.universitas.nama_universitas,
    } : null,
    organizations: (payload.organisasi ?? []).map(normalizeOrganizationOption),
  }
}

export async function getDirectoryOrganizationCounts(universities) {
  const counts = await Promise.all(universities.map(async (university) => {
    const cacheKey = `directory_count_${university.value}`
    const cached = getFromCache(cacheKey, CACHE_TTL_DIRECTORY_COUNTS)
    if (cached !== null) return [university.value, cached]

    const result = await getDirectoryOrganizations(university.value)
    const count = result.organizations.length
    setInCache(cacheKey, count)
    return [university.value, count]
  }))

  return new Map(counts)
}

// Parameter sesuai PencarianProfilOrganisasiController::cari (keyword, id_universitas, jenis_organisasi, id_bidang)
export async function searchOrganizations({ keyword, id_universitas, jenis_organisasi, id_bidang } = {}) {
  const params = {
    keyword: keyword?.trim() || undefined,
    id_universitas: id_universitas || undefined,
    jenis_organisasi: jenis_organisasi ? String(jenis_organisasi).toLowerCase() : undefined,
    id_bidang: id_bidang || undefined,
  }
  const result = await api.get('/organisasi/cari', { params }).then(responseData)
  return Array.isArray(result?.data)
    ? result.data.map((item) => ({
      ...normalizeOrganizationOption(item),
      type: String(item.jenis_organisasi ?? '').toUpperCase(),
    }))
    : []
}

export async function getOrganizationDetail(organizationId) {
  const result = await api.get(`/organisasi/${organizationId}`).then(responseData)
  const item = result?.data
  if (!item) return null

  return {
    ...item,
    id: item.id_organisasi,
    name: item.nama_organisasi,
    type: String(item.jenis_organisasi ?? '').toUpperCase(),
    deskripsi: item.deskripsi ?? '',
    logo: item.logo ? `${assetUrl(item.logo)}${item.logo.includes('?') ? '&' : '?'}v=${Date.now()}` : '',
    portfolio: (item.portofolio ?? []).map((portfolio) => ({
      ...portfolio,
      dokumentasi: assetUrl(portfolio.dokumentasi),
    })),
    contacts: item.kontak ?? [],
    university: item.universitas ? {
      value: item.universitas.id_universitas,
      label: item.universitas.nama_universitas,
      logo: assetUrl(item.universitas.logo),
    } : null,
  }
}

export async function getUniversitas() {
  // Try cache first
  const cached = getFromCache('universitas_v2', CACHE_TTL_UNIVERSITAS)
  if (cached) {
    console.log('[orgApi] Using cached universitas data')
    return cached
  }

  // Fetch dari API
  console.log('[orgApi] Fetching universitas from API')
  const result = await api.get('/master/universitas').then(responseData)
  const items = Array.isArray(result?.data) ? result.data : []
  const data = items.map((item) => ({
    value: item.id_universitas,
    label: item.nama_universitas,
    logo: item.logo,
  }))

  // Save to cache
  setInCache('universitas_v2', data)
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
  const data = { data: items.map(normalizeOrganizationOption) }

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
  const data = { data: items.map(normalizeOrganizationOption) }

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

/**
 * Cari nama BEM induk dari sebuah HMJ/UKM dengan menelusuri anak tiap BEM aktif.
 * Tanpa perubahan backend: hanya bekerja untuk organisasi yang sudah aktif (muncul di daftar publik).
 * @returns {Promise<string|null>}
 */
export async function findBemIndukInfo(organizationId) {
  if (!organizationId) return null
  const bemResult = await getVerifiedBem()
  const parents = await Promise.all(bemResult.data.map(async (bem) => {
    const children = await getVerifiedHmjUkm(bem.organization_id)
    const found = children.data.some((item) => String(item.organization_id) === String(organizationId)
      && item.organization_type !== 'BEM')
    return found ? bem : null
  }))
  const parent = parents.find(Boolean)
  if (!parent) return null

  let universityName = null
  try {
    const universities = await getUniversitas()
    universityName = universities.find((item) => String(item.value) === String(parent.university_id))?.label ?? null
  } catch {
    universityName = null
  }
  return { name: parent.name, universityName }
}

export async function findBemInduk(organizationId) {
  const info = await findBemIndukInfo(organizationId)
  return info?.name ?? null
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
