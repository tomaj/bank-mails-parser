// Rasterize the social cards public/og.svg (English) and public/og-sk.svg (Slovak)
// to public/og.png and public/og-sk.png (1200 × 630).
//   CHROME_PATH=/path/to/chrome node scripts/og-image.mjs
import { readFileSync } from 'node:fs'
import puppeteer from 'puppeteer-core'

const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH || '/usr/bin/google-chrome', args: ['--no-sandbox'] })
const page = await browser.newPage()
await page.setViewport({ width: 1200, height: 630 })
for (const name of ['og', 'og-sk']) {
  const svg = readFileSync(new URL(`../public/${name}.svg`, import.meta.url), 'utf8')
  await page.setContent(`<html><body style="margin:0">${svg.replace('<svg', '<svg width="1200" height="630"')}</body></html>`)
  await page.screenshot({ path: new URL(`../public/${name}.png`, import.meta.url).pathname })
  console.log(`public/${name}.png written`)
}
await browser.close()
