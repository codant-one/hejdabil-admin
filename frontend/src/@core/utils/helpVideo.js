import { themeConfig } from '@themeConfig'

export const resolveHelpVideoThumbnailUrl = thumbnail => {
  if (!thumbnail) return null

  return /^https?:\/\//i.test(thumbnail) ? thumbnail : `${themeConfig.settings.urlStorage}${thumbnail}`
}

export const getHelpVideoThumbnailUrl = helpVideo => {
  if (helpVideo.thumbnail_url) return resolveHelpVideoThumbnailUrl(helpVideo.thumbnail_url)

  const youtubeId = helpVideo.provider === 'youtube'
    ? helpVideo.embed_url?.match(/^https:\/\/www\.youtube\.com\/embed\/([a-zA-Z0-9_-]{11})$/)?.[1]
    : null

  return youtubeId ? `https://i.ytimg.com/vi/${youtubeId}/hqdefault.jpg` : null
}
