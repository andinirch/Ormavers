import api, { responseData } from '../api/api.js'

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
  const result = await api.get('/universitas').then(responseData)
  const items = result?.data?.universitas ?? result?.universitas ?? []
  return items.map((item) => ({
    value: item.id_universitas,
    label: item.nama_universitas,
    logo: item.logo,
  }))
}

export async function getVerifiedBem() {
  const result = await api.get('/public/bem').then(responseData)
  const items = result?.data?.bem ?? result?.bem ?? []
  return { data: items.map(normalizeOrganizationOption), unavailable: false }
}

export async function getVerifiedHmjUkm(bemId) {
  const result = await api.get(`/public/bem/${bemId}/organisasi`).then(responseData)
  const items = result?.data?.organisasi ?? result?.organisasi ?? []
  return { data: items.map(normalizeOrganizationOption), unavailable: false }
}

export async function getOrganizationDirectory(universityId) {
  const universities = await getUniversitas()
  const university = universities.find((item) => String(item.value) === String(universityId))
  if (!university) throw new Error('Universitas tidak ditemukan.')

  const bemResult = await getVerifiedBem()
  const bems = bemResult.data.filter((bem) => String(bem.university_id) === String(universityId))
  const results = await Promise.all(bems.map((bem) => getVerifiedHmjUkm(bem.organization_id)))
  const organizations = results.flatMap((result) => result.data).map((organization) => ({
    id: organization.organization_id,
    name: organization.label,
    type: String(organization.organization_type ?? '').toUpperCase(),
  }))

  return { university, organizations }
}

export async function getUniversityOrganizationCounts(universities) {
  const bemResult = await getVerifiedBem()
  const counts = new Map(universities.map((university) => [university.value, 0]))

  await Promise.all(bemResult.data.map(async (bem) => {
    const organizations = await getVerifiedHmjUkm(bem.organization_id)
    const universityId = universities.find((university) => university.value === bem.university_id)?.value
    if (universityId == null) return
    counts.set(universityId, (counts.get(universityId) ?? 0) + organizations.data.length)
  }))

  return counts
}
