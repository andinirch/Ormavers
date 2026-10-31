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

export async function getDirectoryUniversities(search = '', page = 1) {
  const keyword = search?.trim() ?? ''
  const cacheKey = page === 1 && !keyword ? 'directory_universities' : null
  const cached = cacheKey ? getFromCache(cacheKey, CACHE_TTL_UNIVERSITAS) : null
  if (cached) return cached

  const params = { ...(keyword ? { search: keyword } : {}), ...(page > 1 ? { page } : {}) }
  const result = await api.get('/direktori/universitas', { params: Object.keys(params).length ? params : undefined }).then(responseData)
  const items = Array.isArray(result?.data) ? result.data : result?.data?.universitas ?? []
  const data = items.map((item) => ({
    value: item.id_universitas,
    label: item.nama_universitas,
    logo: item.logo ? `${assetUrl(item.logo)}${item.logo.includes('?') ? '&' : '?'}v=${Date.now()}` : '',
    isDefaultLogo: item.is_default_logo,
  }))
  if (cacheKey) setInCache(cacheKey, data)
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

/**
 * Universitas yang sudah punya BEM aktif (sesuai aturan backend registrasi).
 * @returns {Promise<Array<{value: number, label: string}>>}
 */
export async function getUniversitasAktif() {
  const [bemResult, universities] = await Promise.all([getVerifiedBem(), getUniversitas()])
  const activeIds = new Set(bemResult.data.map((bem) => String(bem.university_id)))
  return universities.filter((item) => activeIds.has(String(item.value)))
}

/**
 * Opsi organisasi untuk dropdown anggota: BEM aktif beserta HMJ/UKM aktif di bawahnya,
 * hanya untuk universitas terpilih. BEM yang gagal dimuat anak-anaknya dilewati tanpa error.
 * @returns {Promise<Array<{value: number, label: string}>>}
 */
export async function getOrganisasiByUniversitasAktif(universityId) {
  if (!universityId) return []
  let items = []
  try {
    const result = await api.get(`/public/universitas/${universityId}/organisasi-lengkap`).then(responseData)
    items = Array.isArray(result?.data?.organisasi)
      ? result.data.organisasi
      : Array.isArray(result?.data) ? result.data : []
  } catch (error) {
    console.warn('[orgApi] organisasi-lengkap gagal dimuat:', error.response?.status ?? error.message)
    return []
  }
  const order = { bem: 1, hmj: 2, ukm: 3 }
  return items
    .filter((item) => !item.status_organisasi || item.status_organisasi === 'aktif')
    .sort((a, b) => (order[a.jenis_organisasi] ?? 9) - (order[b.jenis_organisasi] ?? 9)
      || String(a.nama_organisasi).localeCompare(String(b.nama_organisasi)))
    .map((item) => ({
      value: item.id_organisasi,
      label: item.nama_organisasi,
    }))
}

/**
 * Hitungan organisasi per universitas. Sementara hanya BEM aktif (sumber HMJ/UKM
 * belum tersedia dari backend). Titik ganti: fungsi ini saja.
 */
export async function getUniversityOrganizationCounts(universities, bemData = null) {
  const bemResult = bemData || await getVerifiedBem()
  const counts = new Map(universities.map((university) => [university.value, 0]))

  bemResult.data.forEach((bem) => {
    const universityId = universities.find((university) => String(university.value) === String(bem.university_id))?.value
    if (universityId == null) return
    counts.set(universityId, (counts.get(universityId) ?? 0) + 1)
  })

  return counts
}
