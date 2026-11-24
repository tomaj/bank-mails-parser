import { defineConfig } from 'vitepress'
import { navEn, navSk, sidebar4x, sidebar5x, sidebar5xSk, sidebarSk4x } from '../site-nav'
import { seoHead, siteUrl } from './seo'

const base = '/bank-mails-parser/'
const repositoryBlob = 'https://github.com/tomaj/bank-mails-parser/blob/master/'

const descriptionEn = 'PHP library that reads payment notification emails from Slovak and Czech banks (Tatra banka, VÚB, ČSOB) into typed objects'
const descriptionSk = 'PHP knižnica, ktorá číta e-mailové notifikácie o platbách slovenských a českých bánk (Tatra banka, VÚB, ČSOB) do typových objektov'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Bank Mails Parser',
  description: descriptionEn,
  base,

  head: [
    ['link', { rel: 'icon', href: `${base}favicon.ico`, sizes: '48x48' }],
    ['link', { rel: 'icon', type: 'image/svg+xml', href: `${base}favicon.svg` }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '32x32', href: `${base}favicon-32.png` }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '16x16', href: `${base}favicon-16.png` }],
    ['link', { rel: 'apple-touch-icon', sizes: '180x180', href: `${base}apple-touch-icon.png` }],
    ['link', { rel: 'manifest', href: `${base}site.webmanifest` }],
    ['meta', { name: 'theme-color', content: '#0f766e' }],
  ],

  transformHead({ pageData, siteConfig }) {
    return seoHead(pageData, siteConfig.srcDir, pageData.relativePath.startsWith('sk/') ? descriptionSk : descriptionEn)
  },

  sitemap: {
    hostname: siteUrl,
  },

  markdown: {
    // changelog.md, upgrade.md and security.md include files from the repository root; their
    // relative links to other root files (UPGRADE-5.0.md, SECURITY.md, ...) point at GitHub instead.
    config(md) {
      md.core.ruler.push('repository-root-links', (state) => {
        for (const token of state.tokens) {
          for (const child of token.children ?? []) {
            const href = child.type === 'link_open' ? child.attrGet('href') : null
            if (href !== null && /^[A-Z][A-Z0-9_.-]*\.md(#.*)?$/.test(href)) {
              child.attrSet('href', repositoryBlob + href)
            }
          }
        }
      })
    },
  },

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
    logo: {
      light: '/logo.svg',
      dark: '/logo-dark.svg',
    },

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
      options: {
        locales: {
          sk: {
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
      },
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2015-2026 Tomas Majer',
    },
  },
})
