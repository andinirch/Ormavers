// TODO(kontrak-be): endpoint dan metode HTTP daftar BEM terverifikasi.
// Organization options use the future API shape so the page does not need
// another mapping when the backend is connected.
export async function getVerifiedBem() {
  return { data: [], unavailable: true }
}

function normalizeOrganizationOption(option) {
  const organizationId = option.organization_id ?? option.value
  return {
    ...option,
    value: organizationId,
    organization_id: organizationId,
    organization_type: option.organization_type ?? option.type,
  }
}

// TODO(kontrak-be): return the selected BEM as the first option, followed by
// its verified HMJ/UKM children. The optional BEM option is only metadata from
// the already-loaded verified BEM list, not a replacement for backend checks.
export async function getVerifiedHmjUkm(bemId, bemOption) {
  const data = []
  if (bemOption) {
    data.push(
      normalizeOrganizationOption({
        value: bemId,
        label: `Anggota BEM ${bemOption.label}`,
        organization_id: bemId,
        organization_type: 'bem',
      }),
    )
  }
  return { data, unavailable: true }
}
