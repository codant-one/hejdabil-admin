import { nextTick, unref, watch } from 'vue'

const getScrollableParent = element => {
  let current = element?.parentElement ?? null

  while (current) {
    const styles = window.getComputedStyle(current)
    const overflowY = styles.overflowY
    const canScroll = ['auto', 'scroll', 'overlay'].includes(overflowY)

    if (canScroll && current.scrollHeight > current.clientHeight)
      return current

    current = current.parentElement
  }

  return document.scrollingElement || document.documentElement
}

const scrollElementIntoScrollableParent = ({ element, offset, behavior }) => {
  const scrollParent = getScrollableParent(element)

  if (!scrollParent)
    return

  if (scrollParent === document.scrollingElement || scrollParent === document.documentElement || scrollParent === document.body) {
    const top = window.scrollY + element.getBoundingClientRect().top - offset

    window.scrollTo({
      top: Math.max(0, top),
      behavior,
    })

    return
  }

  const parentRect = scrollParent.getBoundingClientRect()
  const elementRect = element.getBoundingClientRect()
  const top = scrollParent.scrollTop + elementRect.top - parentRect.top - offset

  scrollParent.scrollTo({
    top: Math.max(0, top),
    behavior,
  })
}

export const useMobilePaginationScroll = ({
  targetRef,
  currentPage,
  isRequestOngoing,
  enabled,
  offset = 16,
  behavior = 'smooth',
}) => {
  const shouldScrollOnNextLoad = { value: false }

  const isEnabled = () => {
    if (typeof enabled === 'undefined')
      return true

    return Boolean(unref(enabled))
  }

  const scrollToTarget = async () => {
    if (!isEnabled())
      return

    const element = unref(targetRef)
    if (!element)
      return

    await nextTick()
    scrollElementIntoScrollableParent({
      element,
      offset,
      behavior,
    })
  }

  watch(currentPage, (newPage, oldPage) => {
    if (!isEnabled() || newPage === oldPage)
      return

    shouldScrollOnNextLoad.value = true
  })

  watch(isRequestOngoing, async isLoading => {
    if (!isEnabled() || isLoading || !shouldScrollOnNextLoad.value)
      return

    await scrollToTarget()
    shouldScrollOnNextLoad.value = false
  })

  return {
    scrollToTarget,
  }
}
