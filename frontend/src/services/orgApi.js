import api, { responseData } from '../api/api.js'

function normalizeOrganizationOption(option) {
  const organizationId = option.id_organisasi ?? option.organization_id ?? option.value
  return {
    ...option,
    value: organizationId,
    label: option.nama_organisasi ?? option.label,
    organization_id: organizationId,
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
