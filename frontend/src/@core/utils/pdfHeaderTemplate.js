export const resolveLogoUrl = (logoPath, themeConfig) => {
  if (!logoPath)
    return null

  const normalized = String(logoPath).trim()

  if (/^https?:\/\//i.test(normalized) || normalized.startsWith('data:'))
    return normalized

  const withoutLeadingSlash = normalized.replace(/^\/+/, '')

  if (withoutLeadingSlash.startsWith('storage/'))
    return `${themeConfig.settings.urlPublic}${withoutLeadingSlash}`

  return `${themeConfig.settings.urlStorage}${withoutLeadingSlash}`
}

const canLoadImage = imageUrl => new Promise(resolve => {
  if (!imageUrl)
    return resolve(false)

  const image = new Image()
  image.crossOrigin = 'anonymous'
  image.onload = () => resolve(true)
  image.onerror = () => resolve(false)
  image.src = imageUrl
})

export const buildPdfTopHeader = async ({
  company,
  title = 'SWISH',
  themeConfig,
  escapeHtml,
  showCompanyDetailsWhenLogo = true,
}) => {
  const companyName = company?.company || 'Swish'
  const contactName = `${company?.name ?? ''} ${company?.last_name ?? ''}`.trim()
  const contactEmail = company?.email ?? ''

  const companyLogoCandidate = resolveLogoUrl(company?.logo, themeConfig)
  const companyLogo = await canLoadImage(companyLogoCandidate)
    ? companyLogoCandidate
    : null

  const companyMarkup = companyLogo
    ? `
      <div style="width: 200px; height: 90px; background: #FFFFFF; border-radius: 8px; position: relative; padding: 8px;">
        <img src="${escapeHtml(companyLogo)}" alt="logo-main" crossorigin="anonymous" style="display: block; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 185px; max-height: 74px; object-fit: contain;" />
      </div>
    `
    : `
      <h1 style="margin: 0; font-size: 12px; line-height: 1.2;">${escapeHtml(companyName)}</h1>
      <div style="margin-top: 6px; line-height: 1.4; color: #454545;">
        ${escapeHtml(contactName)}<br />
        ${escapeHtml(contactEmail)}
      </div>
    `

  const companyDetailsMarkup = `
    <div style="margin-top: 10px; line-height: 1.4; color: #454545; text-align: right;">
      <h1 style="margin: 0; font-size: 12px; line-height: 1.2; font-weight: 600;">${escapeHtml(companyName)}</h1>
      <div style="margin-top: 4px;">
        ${escapeHtml(contactName)}<br />
        ${escapeHtml(contactEmail)}
      </div>
    </div>
  `

  const rightHeaderMarkup = `
    <td style="width: 65%; padding: 16px; vertical-align: middle;">
      <div style="text-align: right;">
        <span style="margin: 0; font-size: 32px; font-weight: 600; color: #454545; border-top: 1px solid #454545; border-bottom: 1px solid #454545; padding: 8px 16px 12px 16px; line-height: 0.6; display: inline-block; letter-spacing: 0 !important;">
          ${escapeHtml(title)}
        </span>
        ${companyLogo && showCompanyDetailsWhenLogo ? companyDetailsMarkup : ''}
      </div>
    </td>
  `

  const headerMarkup = `
    <table style="width: 100%; background-color: #E7E7E7; border-radius: 16px; border-radius: 16px;">
      <tr>
        <td style="width: 35%; padding: 16px; vertical-align: middle;">
          ${companyMarkup}
        </td>
        ${rightHeaderMarkup}
      </tr>
    </table>
  `

  return {
    headerMarkup,
    hasLogo: !!companyLogo,
  }
}