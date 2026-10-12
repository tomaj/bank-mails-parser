export interface DocsNavItem {
  text: string
  link?: string
  items?: DocsNavItem[]
}

export const currentVersion = '5.x'

const coverageLink = 'https://tomaj.github.io/bank-mails-parser/coverage/'
const releasesLink = 'https://github.com/tomaj/bank-mails-parser/releases'

function versionNav(latestLink: string, latestText: string, archiveLink: string): DocsNavItem {
  return {
    text: currentVersion,
    items: [
      { text: latestText, link: latestLink },
      { text: '4.x', link: archiveLink },
    ],
  }
}

export const navEn: DocsNavItem[] = [
  { text: 'Home', link: '/' },
  { text: 'Guide', link: '/guide/getting-started' },
  { text: 'API', link: '/api/reference' },
  versionNav('/', '5.x (latest)', '/4.x/'),
  { text: 'Coverage', link: coverageLink },
  { text: 'Releases', link: releasesLink },
]

export const navSk: DocsNavItem[] = [
  { text: 'Domov', link: '/sk/' },
  { text: 'Príručka', link: '/sk/guide/getting-started' },
  { text: 'API', link: '/sk/api/reference' },
  versionNav('/sk/', '5.x (aktuálna)', '/sk/4.x/'),
  { text: 'Pokrytie', link: coverageLink },
  { text: 'Vydania', link: releasesLink },
]

export const sidebar5x: DocsNavItem[] = [
  {
    text: 'Introduction',
    items: [
      { text: 'Getting Started', link: '/guide/getting-started' },
      { text: 'Examples', link: '/guide/examples' },
    ],
  },
  {
    text: 'Guide',
    items: [
      { text: 'TatraBanka', link: '/guide/tatrabanka' },
      { text: 'ČSOB Czech Republic', link: '/guide/csob-cz' },
      { text: 'ČSOB Slovakia', link: '/guide/csob-sk' },
      { text: 'VÚB', link: '/guide/vub' },
      { text: 'Bank Detection', link: '/guide/bank-detection' },
      { text: 'Exceptions', link: '/guide/exceptions' },
      { text: 'Adding New Bank', link: '/guide/adding-bank' },
    ],
  },
  {
    text: 'Reference',
    items: [
      { text: 'API Reference', link: '/api/reference' },
      { text: 'Changelog', link: '/changelog' },
    ],
  },
  {
    text: 'Contributing',
    items: [
      { text: 'Contributing Guide', link: '/contributing' },
      { text: 'Versioning Docs', link: '/guide/versioning' },
    ],
  },
]

export const sidebar5xSk: DocsNavItem[] = [
  {
    text: 'Úvod',
    items: [
      { text: 'Začíname', link: '/sk/guide/getting-started' },
      { text: 'Príklady', link: '/sk/guide/examples' },
    ],
  },
  {
    text: 'Príručka',
    items: [
      { text: 'TatraBanka', link: '/sk/guide/tatrabanka' },
      { text: 'ČSOB Česká republika', link: '/sk/guide/csob-cz' },
      { text: 'ČSOB Slovensko', link: '/sk/guide/csob-sk' },
      { text: 'VÚB', link: '/sk/guide/vub' },
      { text: 'Rozpoznanie banky', link: '/sk/guide/bank-detection' },
      { text: 'Výnimky', link: '/sk/guide/exceptions' },
      { text: 'Pridanie novej banky', link: '/sk/guide/adding-bank' },
    ],
  },
  {
    text: 'Referencia',
    items: [
      { text: 'Referencia API', link: '/sk/api/reference' },
      { text: 'Zoznam zmien', link: '/sk/changelog' },
    ],
  },
  {
    text: 'Prispievanie',
    items: [
      { text: 'Ako prispieť', link: '/sk/contributing' },
      { text: 'Verzie dokumentácie', link: '/sk/guide/versioning' },
    ],
  },
]

export const sidebar4x: DocsNavItem[] = [
  {
    text: 'Introduction',
    items: [
      { text: 'Getting Started', link: '/4.x/guide/getting-started' },
      { text: 'Examples', link: '/4.x/guide/examples' },
    ],
  },
  {
    text: 'Guide',
    items: [
      { text: 'TatraBanka', link: '/4.x/guide/tatrabanka' },
      { text: 'ČSOB Czech Republic', link: '/4.x/guide/csob-cz' },
      { text: 'ČSOB Slovakia', link: '/4.x/guide/csob-sk' },
      { text: 'VÚB', link: '/4.x/guide/vub' },
      { text: 'Adding New Bank', link: '/4.x/guide/adding-bank' },
    ],
  },
  {
    text: 'Reference',
    items: [
      { text: 'API Reference', link: '/4.x/api/reference' },
      { text: 'Changelog', link: '/changelog' },
    ],
  },
  {
    text: 'Contributing',
    items: [
      { text: 'Contributing Guide', link: '/contributing' },
      { text: 'Versioning Docs', link: '/guide/versioning' },
    ],
  },
]

export const sidebarSk4x: DocsNavItem[] = [
  {
    text: 'Úvod',
    items: [
      { text: 'Začíname', link: '/sk/4.x/guide/getting-started' },
      { text: 'Príklady', link: '/sk/4.x/guide/examples' },
    ],
  },
  {
    text: 'Príručka',
    items: [
      { text: 'TatraBanka', link: '/sk/4.x/guide/tatrabanka' },
      { text: 'ČSOB Česká republika', link: '/sk/4.x/guide/csob-cz' },
      { text: 'ČSOB Slovensko', link: '/sk/4.x/guide/csob-sk' },
      { text: 'VÚB', link: '/sk/4.x/guide/vub' },
      { text: 'Pridanie novej banky', link: '/sk/4.x/guide/adding-bank' },
    ],
  },
  {
    text: 'Referencia',
    items: [
      { text: 'Referencia API', link: '/sk/4.x/api/reference' },
      { text: 'Zoznam zmien', link: '/sk/changelog' },
    ],
  },
  {
    text: 'Prispievanie',
    items: [
      { text: 'Ako prispieť', link: '/sk/contributing' },
      { text: 'Verzie dokumentácie', link: '/sk/guide/versioning' },
    ],
  },
]
