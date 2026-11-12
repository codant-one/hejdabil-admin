export const getHelpVideoThumbnailUrl = helpVideo => {
  if (helpVideo.thumbnail_url) return helpVideo.thumbnail_url

  const youtubeId = helpVideo.provider === 'youtube'
    ? helpVideo.embed_url?.match(/^https:\/\/www\.youtube\.com\/embed\/([a-zA-Z0-9_-]{11})$/)?.[1]
    : null

  return youtubeId ? `https://i.ytimg.com/vi/${youtubeId}/hqdefault.jpg` : null
}
