import { defineConfig } from 'vitepress'
import { navEn, navSk, sidebar4x, sidebar5x, sidebar5xSk, sidebarSk4x } from '../site-nav'

const descriptionEn = 'Professional PHP library for parsing bank confirmation emails from Slovak and Czech banks'
const descriptionSk = 'PHP knižnica na spracovanie potvrdzujúcich e-mailov slovenských a českých bánk'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Bank Mails Parser',
  description: descriptionEn,
  base: '/bank-mails-parser/',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/bank-mails-parser/favicon.svg' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Bank Mails Parser - PHP Banking Email Parser' }],
    ['meta', { property: 'og:description', content: descriptionEn }],
    ['meta', { property: 'og:url', content: 'https://tomaj.github.io/bank-mails-parser/' }],
    ['meta', { property: 'og:image', content: 'https://tomaj.github.io/bank-mails-parser/logo.svg' }],
    ['meta', { name: 'twitter:card', content: 'summary' }],
    ['meta', { name: 'twitter:title', content: 'Bank Mails Parser - PHP Banking Email Parser' }],
    ['meta', { name: 'twitter:description', content: descriptionEn }],
  ],

  lastUpdated: true,

  locales: {
    root: {
      label: 'EN',
      lang: 'en-US',
      description: descriptionEn,
    },
    sk: {
      label: 'SK',
      lang: 'sk',
      description: descriptionSk,
      link: '/sk/',
      head: [
        ['meta', { property: 'og:title', content: 'Bank Mails Parser - spracovanie bankových e-mailov v PHP' }],
        ['meta', { property: 'og:description', content: descriptionSk }],
        ['meta', { name: 'twitter:title', content: 'Bank Mails Parser - spracovanie bankových e-mailov v PHP' }],
        ['meta', { name: 'twitter:description', content: descriptionSk }],
      ],
      themeConfig: {
        nav: navSk,
        sidebar: {
          '/sk/4.x/': sidebarSk4x,
          '/sk/': sidebar5xSk,
        },
        outline: {
          label: 'Na tejto stránke',
        },
        docFooter: {
          prev: 'Predchádzajúca stránka',
          next: 'Ďalšia stránka',
        },
        editLink: {
          pattern: 'https://github.com/tomaj/bank-mails-parser/edit/master/docs/:path',
          text: 'Upraviť túto stránku na GitHube',
        },
        lastUpdated: {
          text: 'Posledná aktualizácia',
        },
        darkModeSwitchLabel: 'Vzhľad',
        lightModeSwitchTitle: 'Prepnúť na svetlý režim',
        darkModeSwitchTitle: 'Prepnúť na tmavý režim',
        sidebarMenuLabel: 'Ponuka',
        returnToTopLabel: 'Návrat na začiatok',
        langMenuLabel: 'Zmeniť jazyk',
        skipToContentLabel: 'Preskočiť na obsah',
        footer: {
          message: 'Vydané pod licenciou MIT.',
          copyright: 'Copyright © 2015-2026 Tomas Majer',
        },
        search: {
          provider: 'local',
          options: {
            translations: {
              button: {
                buttonText: 'Hľadať',
                buttonAriaLabel: 'Hľadať',
              },
              modal: {
                displayDetails: 'Zobraziť podrobný zoznam',
                resetButtonTitle: 'Vymazať hľadanie',
                backButtonTitle: 'Zavrieť hľadanie',
                noResultsText: 'Žiadne výsledky pre',
                footer: {
                  selectText: 'vybrať',
                  selectKeyAriaLabel: 'enter',
                  navigateText: 'prejsť',
                  navigateUpKeyAriaLabel: 'šípka hore',
                  navigateDownKeyAriaLabel: 'šípka dole',
                  closeText: 'zavrieť',
                  closeKeyAriaLabel: 'escape',
                },
              },
            },
          },
        },
        notFound: {
          code: '404',
          title: 'STRÁNKA SA NENAŠLA',
          quote: 'Ak nezmeníte smer a budete hľadať ďalej, môžete doraziť práve tam, kam mierite.',
          linkLabel: 'prejsť na úvod',
          linkText: 'Späť na úvod',
        },
      },
    },
  },

  transformHtml(code, _id, ctx) {
    if (ctx.page.startsWith('4.x/') || ctx.page.includes('/4.x/')) {
      return code.replace(/>5\.x</g, '>4.x<')
    }
    return code
  },

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/logo.svg',

    nav: navEn,

    sidebar: {
      '/4.x/': sidebar4x,
      '/': sidebar5x,
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/tomaj/bank-mails-parser' },
    ],

    editLink: {
      pattern: 'https://github.com/tomaj/bank-mails-parser/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2015-2026 Tomas Majer',
    },
  },
})
