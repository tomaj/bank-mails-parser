// Per-page SEO: canonical URL, Open Graph and Twitter tags, hreflang alternates and JSON-LD.
// Used by transformHead in config.mts.
import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import type { HeadConfig, PageData } from 'vitepress'

export const siteUrl = 'https://tomaj.github.io/bank-mails-parser/'
export const ogImage = `${siteUrl}og.png`
// Slovak pages share the Slovak social card.
export const ogImageSk = `${siteUrl}og-sk.png`
const siteName = 'Bank Mails Parser'
const repoUrl = 'https://github.com/tomaj/bank-mails-parser'

const person = {
  '@type': 'Person',
  '@id': `${siteUrl}#tomaj`,
  name: 'Tomas Majer',
  url: 'https://github.com/tomaj',
}

export function pageUrl(relativePath: string): string {
  const path = relativePath.replace(/(^|\/)index\.md$/, '$1').replace(/\.md$/, '.html')
  return siteUrl + path
}

// English pages live at the site root, Slovak pages mirror them under sk/.
export function localeOf(relativePath: string): { prefix: string, lang: string, ogLocale: string } {
  return relativePath.startsWith('sk/')
    ? { prefix: 'sk/', lang: 'sk', ogLocale: 'sk_SK' }
    : { prefix: '', lang: 'en-US', ogLocale: 'en_US' }
}

// Section names for the breadcrumb, from the first path segment after the locale.
const sections: Record<string, Record<string, [string, string]>> = {
  '': {
    guide: ['Guide', 'guide/getting-started.html'],
    examples: ['Examples', 'examples/'],
    api: ['Reference', 'api/reference.html'],
    '4.x': ['Bank Mails Parser 4.x', '4.x/'],
  },
  'sk/': {
    guide: ['Príručka', 'guide/getting-started.html'],
    examples: ['Príklady', 'examples/'],
    api: ['Referencia', 'api/reference.html'],
    '4.x': ['Bank Mails Parser 4.x', '4.x/'],
  },
}

// The same page in the other locale, when it exists, for hreflang links.
export function alternates(relativePath: string, srcDir: string): { lang: string, href: string }[] {
  const { prefix } = localeOf(relativePath)
  const base = relativePath.slice(prefix.length)
  const pairs: [string, string][] = [['en', base], ['sk', `sk/${base}`]]
  const found = pairs.filter(([, path]) => existsSync(resolve(srcDir, path)))
  if (found.length < 2) return []
  return [
    ...found.map(([lang, path]) => ({ lang, href: pageUrl(path) })),
    { lang: 'x-default', href: pageUrl(base) },
  ]
}

function breadcrumb(page: PageData, url: string) {
  const { prefix } = localeOf(page.relativePath)
  const home = siteUrl + prefix
  const path = page.relativePath.slice(prefix.length)
  const items: { name: string, item: string }[] = [{ name: siteName, item: home }]
  const section = sections[prefix][path.split('/')[0]]
  if (section && home + section[1] !== url) items.push({ name: section[0], item: home + section[1] })
  if (url !== home) items.push({ name: page.title, item: url })
  return {
    '@type': 'BreadcrumbList',
    itemListElement: items.map((entry, i) => ({ '@type': 'ListItem', position: i + 1, name: entry.name, item: entry.item })),
  }
}

// Markdown to plain text, enough for JSON-LD answers.
export function plain(markdown: string): string {
  return markdown
    .replace(/```[\s\S]*?```/g, '')
    .replace(/^<<< .*$/gm, '')
    .replace(/<[^>]+>/g, '')
    .replace(/!\[[^\]]*\]\([^)]*\)/g, '')
    .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
    .replace(/[`*_]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

// On faq.md every `##` heading is a question.
export function faqEntries(source: string, relativePath: string): { q: string, a: string }[] {
  if (relativePath !== 'faq.md' && relativePath !== 'sk/faq.md') return []
  return ('\n' + source).split('\n## ').slice(1)
    .map((part) => {
      const [q, ...rest] = part.split('\n')
      return { q: plain(q), a: plain(rest.join('\n')) }
    })
    .filter((e) => e.q && e.a)
}

export function seoHead(page: PageData, srcDir: string, siteDescription: string): HeadConfig[] {
  if (page.isNotFound || !page.relativePath) return []
  const url = pageUrl(page.relativePath)
  const locale = localeOf(page.relativePath)
  const sk = locale.prefix === 'sk/'
  const image = sk ? ogImageSk : ogImage
  const isHome = page.frontmatter.layout === 'home'
  const homeTitle = sk ? 'Bank Mails Parser - bankové notifikácie v PHP' : 'Bank Mails Parser - bank notification emails in PHP'
  const title = isHome ? homeTitle : `${page.title} | ${siteName}`
  const description = page.description || page.frontmatter.description || siteDescription
  const source = readFileSync(resolve(srcDir, page.relativePath), 'utf8')
  const modified = page.lastUpdated ? new Date(page.lastUpdated).toISOString() : undefined

  const head: HeadConfig[] = [
    ['link', { rel: 'canonical', href: url }],
    ['meta', { property: 'og:type', content: isHome ? 'website' : 'article' }],
    ['meta', { property: 'og:site_name', content: siteName }],
    ['meta', { property: 'og:locale', content: locale.ogLocale }],
    ['meta', { property: 'og:locale:alternate', content: sk ? 'en_US' : 'sk_SK' }],
    ['meta', { property: 'og:title', content: title }],
    ['meta', { property: 'og:description', content: description }],
    ['meta', { property: 'og:url', content: url }],
    ['meta', { property: 'og:image', content: image }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { property: 'og:image:alt', content: sk ? 'Bank Mails Parser: bankové notifikácie spracované na typové PHP objekty' : 'Bank Mails Parser: bank notification emails parsed into typed PHP objects' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: title }],
    ['meta', { name: 'twitter:description', content: description }],
    ['meta', { name: 'twitter:image', content: image }],
    ['meta', { name: 'author', content: person.name }],
  ]
  for (const alt of alternates(page.relativePath, srcDir)) {
    head.push(['link', { rel: 'alternate', hreflang: alt.lang, href: alt.href }])
  }
  if (modified && !isHome) head.push(['meta', { property: 'article:modified_time', content: modified }])

  const website = { '@type': 'WebSite', '@id': `${siteUrl}#website`, name: siteName, url: siteUrl, inLanguage: ['en-US', 'sk'], publisher: { '@id': person['@id'] } }
  const graph: Record<string, unknown>[] = [person, website]

  if (isHome) {
    graph.push({
      '@type': 'SoftwareSourceCode',
      '@id': `${siteUrl}#software`,
      name: siteName,
      description: siteDescription,
      url: siteUrl,
      codeRepository: repoUrl,
      programmingLanguage: { '@type': 'ComputerLanguage', name: 'PHP' },
      runtimePlatform: 'PHP 8.4',
      license: 'https://opensource.org/licenses/MIT',
      author: { '@id': person['@id'] },
      image,
    })
  } else {
    graph.push({
      '@type': 'TechArticle',
      '@id': `${url}#article`,
      headline: page.title,
      description,
      url,
      mainEntityOfPage: url,
      inLanguage: locale.lang,
      image,
      author: { '@id': person['@id'] },
      publisher: { '@id': person['@id'] },
      isPartOf: { '@id': `${siteUrl}#website` },
      about: { '@type': 'SoftwareSourceCode', name: siteName, codeRepository: repoUrl },
      ...(modified ? { dateModified: modified } : {}),
    })
    graph.push(breadcrumb(page, url))
  }

  const faq = faqEntries(source, page.relativePath)
  if (faq.length > 0) {
    graph.push({
      '@type': 'FAQPage',
      '@id': `${url}#faq`,
      url,
      mainEntity: faq.map((e) => ({ '@type': 'Question', name: e.q, acceptedAnswer: { '@type': 'Answer', text: e.a } })),
    })
  }

  head.push(['script', { type: 'application/ld+json' }, JSON.stringify({ '@context': 'https://schema.org', '@graph': graph })])
  return head
}
