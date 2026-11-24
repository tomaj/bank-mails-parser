// Rasterize the social card public/og.svg to public/og.png (1200 × 630).
//   CHROME_PATH=/path/to/chrome node scripts/og-image.mjs
import { readFileSync } from 'node:fs'
import puppeteer from 'puppeteer-core'

const svg = readFileSync(new URL('../public/og.svg', import.meta.url), 'utf8')
const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH || '/usr/bin/google-chrome', args: ['--no-sandbox'] })
const page = await browser.newPage()
await page.setViewport({ width: 1200, height: 630 })
await page.setContent(`<html><body style="margin:0">${svg.replace('<svg', '<svg width="1200" height="630"')}</body></html>`)
await page.screenshot({ path: new URL('../public/og.png', import.meta.url).pathname })
await browser.close()
console.log('public/og.png written')
