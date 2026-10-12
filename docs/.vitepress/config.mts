import { defineConfig } from 'vitepress'
import { currentVersion, sidebar4x, sidebar5x, versions } from '../site-nav'

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

  transformHtml(code, _id, ctx) {
    if (ctx.page.startsWith('4.x/') || ctx.page.includes('/4.x/')) {
      return code.replace(/>5\.x</g, '>4.x<')
    }
    return code
  },

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

    sidebar: {
      '/4.x/': sidebar4x,
      '/': sidebar5x
    },

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
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2015-2026 Tomas Majer'
    }
  }
})
