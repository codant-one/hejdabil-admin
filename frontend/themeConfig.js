import { VIcon } from 'vuetify/components'

// ❗ Logo SVG must be imported with ?raw suffix
import { defineThemeConfig } from '@core'
import { RouteTransitions, Skins } from '@core/enums'
import logo from '@images/logo.svg?raw'
import logoWhite from '@images/logo_white.svg'
import logoFull from '@images/logos/billogg-logo.svg'
import { AppContentLayoutNav, ContentWidth, FooterType, NavbarType } from '@layouts/enums'

export const { themeConfig, layoutConfig } = defineThemeConfig({
  app: {
    title: import.meta.env.VITE_APP_TITLE,
    logo: h('div', { innerHTML: logo, style: 'line-height:0; color: rgb(var(--v-global-theme-primary))' }),
    logoFull: h('img', { src: logoFull }),
    logoWhite: h('img', { src: logoWhite, style: 'width: 35px' }),
    contentWidth: ContentWidth.Boxed,
    contentLayoutNav: AppContentLayoutNav.Vertical,
    overlayNavFromBreakpoint: 1024, // Usar valor directo en lugar de breakpointsVuetify.lg
    enableI18n: false,
    theme: 'light',
    isRtl: false,
    skin: Skins.Default,
    routeTransition: RouteTransitions.Fade,
    iconRenderer: VIcon,
  },
  navbar: {
    type: NavbarType.Sticky,
    navbarBlur: true,
  },
  footer: { type: FooterType.Static },
  verticalNav: {
    isVerticalNavCollapsed: false,
    defaultNavItemIconProps: { icon: 'custom-point' },
    isVerticalNavSemiDark: true,
  },
  horizontalNav: {
    type: 'sticky',
    transition: 'slide-y-reverse-transition',
  },
  icons: {
    chevronDown: { icon: 'custom-chevron-down' },
    chevronRight: { icon: 'custom-chevron-right', size: 16 },
    close: { icon: 'tabler-x' },
    verticalNavPinned: { icon: 'tabler-circle-dot' },
    verticalNavUnPinned: { icon: 'tabler-circle' },
    sectionTitlePlaceholder: { icon: 'tabler-separator' },
  },
  settings: {
    urlbase: import.meta.env.VITE_APP_DOMAIN_API_URL + '/api/',
    urlStorage: import.meta.env.VITE_APP_DOMAIN_API_URL + '/storage/',
    urlPublic: import.meta.env.VITE_APP_DOMAIN_API_URL + '/',
    urlDomain: import.meta.env.VITE_APP_DOMAIN_URL + '/'
  },
})
