import DefaultTheme from 'vitepress/theme'
import { inBrowser } from 'vitepress'
import './custom.css'

function versionLabel(path: string): string {
  return path.includes('/4.x/') ? '4.x' : '5.x'
}

function showVersion(path: string): void {
  const label = versionLabel(path)
  document.querySelectorAll('.VPNavBarMenu .text, .VPNavBarExtra .text').forEach((node) => {
    const text = node.textContent?.trim()
    if (text === '5.x' || text === '4.x') {
      node.textContent = label
    }
  })
}

export default {
  extends: DefaultTheme,
  enhanceApp({ router }) {
    if (!inBrowser) {
      return
    }
    const apply = (path: string) => {
      showVersion(path)
      window.setTimeout(() => showVersion(path), 0)
    }
    router.onAfterRouteChanged = apply
    window.addEventListener('load', () => apply(location.pathname))
  },
}
