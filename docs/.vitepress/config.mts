import { defineConfig } from 'vitepress'

// Version configuration
const currentVersion = '5.x'
const versions = [
  { text: `Latest (${currentVersion})`, link: '/bank-mails-parser/' },
  { text: '4.x', link: 'https://github.com/tomaj/bank-mails-parser/tree/docs-archive/4.x/docs' }
  // Archived versions are available in docs-archive/* branches
]

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Bank Mails Parser",
  description: "Professional PHP library for parsing bank confirmation emails from Slovak and Czech banks",
  base: '/bank-mails-parser/',
  
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/bank-mails-parser/favicon.svg' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Bank Mails Parser - PHP Banking Email Parser' }],
    ['meta', { property: 'og:description', content: 'Professional PHP library for parsing bank confirmation emails from Slovak and Czech banks' }],
    ['meta', { property: 'og:url', content: 'https://tomaj.github.io/bank-mails-parser/' }],
    ['meta', { property: 'og:image', content: 'https://tomaj.github.io/bank-mails-parser/logo.svg' }],
    ['meta', { name: 'twitter:card', content: 'summary' }],
    ['meta', { name: 'twitter:title', content: 'Bank Mails Parser - PHP Banking Email Parser' }],
    ['meta', { name: 'twitter:description', content: 'Professional PHP library for parsing bank confirmation emails from Slovak and Czech banks' }],
  ],

  lastUpdated: true,

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/logo.svg',
    
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API', link: '/api/reference' },
      {
        text: currentVersion,
        items: versions
      },
      { text: 'Coverage', link: 'https://tomaj.github.io/bank-mails-parser/coverage/' },
      { text: 'Releases', link: 'https://github.com/tomaj/bank-mails-parser/releases' }
    ],

    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'Getting Started', link: '/guide/getting-started' },
          { text: 'Examples', link: '/guide/examples' }
        ]
      },
      {
        text: 'Guide',
        items: [
          { text: 'TatraBanka', link: '/guide/tatrabanka' },
          { text: 'ČSOB Czech Republic', link: '/guide/csob-cz' },
          { text: 'ČSOB Slovakia', link: '/guide/csob-sk' },
          { text: 'VÚB', link: '/guide/vub' },
          { text: 'Adding New Bank', link: '/guide/adding-bank' }
        ]
      },
      {
        text: 'Reference',
        items: [
          { text: 'API Reference', link: '/api/reference' },
          { text: 'Changelog', link: '/changelog' }
        ]
      },
      {
        text: 'Contributing',
        items: [
          { text: 'Contributing Guide', link: '/contributing' },
          { text: 'Versioning Docs', link: '/guide/versioning' }
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/tomaj/bank-mails-parser' }
    ],

    editLink: {
      pattern: 'https://github.com/tomaj/bank-mails-parser/edit/master/docs/:path',
      text: 'Edit this page on GitHub'
    },

    search: {
      provider: 'local'
    },

    footer: {
      message: 'See LICENCE file for license information.',
      copyright: 'Copyright © 2015-2026 Tomas Majer'
    }
  }
})
