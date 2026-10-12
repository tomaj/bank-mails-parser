export interface DocsNavItem {
  text: string
  link?: string
  items?: DocsNavItem[]
}

export const currentVersion = '5.x'

export const versions: DocsNavItem[] = [
  { text: '5.x (latest)', link: '/' },
  { text: '4.x', link: '/4.x/' },
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
