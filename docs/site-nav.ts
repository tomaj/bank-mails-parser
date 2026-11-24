export interface DocsNavItem {
  text: string
  link?: string
  items?: DocsNavItem[]
}

export const currentVersion = '5.x'

const coverageLink = 'https://tomaj.github.io/bank-mails-parser/coverage/'
const releasesLink = 'https://github.com/tomaj/bank-mails-parser/releases'

function versionNav(latestLink: string, latestText: string, archiveLink: string, upgrade: DocsNavItem, changelog: DocsNavItem): DocsNavItem {
  return {
    text: currentVersion,
    items: [
      { text: latestText, link: latestLink },
      { text: '4.x', link: archiveLink },
      upgrade,
      changelog,
    ],
  }
}

export const navEn: DocsNavItem[] = [
  { text: 'Home', link: '/' },
  { text: 'Guide', link: '/guide/getting-started' },
  { text: 'Examples', link: '/examples/' },
  { text: 'API', link: '/api/reference' },
  versionNav('/', '5.x (latest)', '/4.x/', { text: 'Upgrading from 4.x', link: '/upgrade' }, { text: 'Changelog', link: '/changelog' }),
  { text: 'Coverage', link: coverageLink },
  { text: 'Releases', link: releasesLink },
]

export const navSk: DocsNavItem[] = [
  { text: 'Domov', link: '/sk/' },
  { text: 'Príručka', link: '/sk/guide/getting-started' },
  { text: 'Príklady', link: '/sk/examples/' },
  { text: 'API', link: '/sk/api/reference' },
  versionNav('/sk/', '5.x (aktuálna)', '/sk/4.x/', { text: 'Prechod zo 4.x', link: '/sk/upgrade' }, { text: 'Zoznam zmien', link: '/sk/changelog' }),
  { text: 'Pokrytie', link: coverageLink },
  { text: 'Vydania', link: releasesLink },
]

export const sidebar5x: DocsNavItem[] = [
  {
    text: 'Introduction',
    items: [
      { text: 'Getting Started', link: '/guide/getting-started' },
      { text: 'Configuration', link: '/guide/configuration' },
      { text: 'Bank Detection', link: '/guide/bank-detection' },
      { text: 'Exceptions', link: '/guide/exceptions' },
    ],
  },
  {
    text: 'Banks',
    items: [
      { text: 'TatraBanka', link: '/guide/tatrabanka' },
      { text: 'ČSOB Czech Republic', link: '/guide/csob-cz' },
      { text: 'ČSOB Slovakia', link: '/guide/csob-sk' },
      { text: 'VÚB', link: '/guide/vub' },
    ],
  },
  {
    text: 'Examples',
    items: [
      { text: 'Overview', link: '/examples/' },
      { text: 'Tatra banka b-mail', link: '/examples/tatra-banka' },
      { text: 'CardPay and ComfortPay', link: '/examples/cardpay' },
      { text: 'VÚB', link: '/examples/vub' },
      { text: 'ČSOB SK and CZ', link: '/examples/csob' },
      { text: 'A payments mailbox', link: '/examples/mailbox' },
      { text: 'Encrypted card statement', link: '/examples/encrypted-statement' },
      { text: 'Error handling', link: '/examples/error-handling' },
      { text: 'Custom parser', link: '/examples/custom-parser' },
      { text: 'More snippets', link: '/guide/examples' },
    ],
  },
  {
    text: 'Integration',
    items: [
      { text: 'Testing your integration', link: '/guide/testing' },
      { text: 'Security', link: '/security' },
      { text: 'Adding a New Bank', link: '/guide/adding-bank' },
    ],
  },
  {
    text: 'Reference',
    items: [
      { text: 'API Reference', link: '/api/reference' },
      { text: 'FAQ', link: '/faq' },
      { text: 'Upgrading from 4.x', link: '/upgrade' },
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
      { text: 'Konfigurácia', link: '/sk/guide/configuration' },
      { text: 'Rozpoznanie banky', link: '/sk/guide/bank-detection' },
      { text: 'Výnimky', link: '/sk/guide/exceptions' },
    ],
  },
  {
    text: 'Banky',
    items: [
      { text: 'TatraBanka', link: '/sk/guide/tatrabanka' },
      { text: 'ČSOB Česká republika', link: '/sk/guide/csob-cz' },
      { text: 'ČSOB Slovensko', link: '/sk/guide/csob-sk' },
      { text: 'VÚB', link: '/sk/guide/vub' },
    ],
  },
  {
    text: 'Príklady',
    items: [
      { text: 'Prehľad', link: '/sk/examples/' },
      { text: 'b-mail Tatra banky', link: '/sk/examples/tatra-banka' },
      { text: 'CardPay a ComfortPay', link: '/sk/examples/cardpay' },
      { text: 'VÚB', link: '/sk/examples/vub' },
      { text: 'ČSOB SK a CZ', link: '/sk/examples/csob' },
      { text: 'Schránka s platbami', link: '/sk/examples/mailbox' },
      { text: 'Šifrovaný výpis kariet', link: '/sk/examples/encrypted-statement' },
      { text: 'Ošetrenie chýb', link: '/sk/examples/error-handling' },
      { text: 'Vlastný parser', link: '/sk/examples/custom-parser' },
      { text: 'Ďalšie ukážky', link: '/sk/guide/examples' },
    ],
  },
  {
    text: 'Integrácia',
    items: [
      { text: 'Testovanie integrácie', link: '/sk/guide/testing' },
      { text: 'Bezpečnosť', link: '/sk/security' },
      { text: 'Pridanie novej banky', link: '/sk/guide/adding-bank' },
    ],
  },
  {
    text: 'Referencia',
    items: [
      { text: 'Referencia API', link: '/sk/api/reference' },
      { text: 'Časté otázky', link: '/sk/faq' },
      { text: 'Prechod zo 4.x', link: '/sk/upgrade' },
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
