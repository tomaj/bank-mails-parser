// Rasterize public/favicon.svg to the PNG icons and favicon.ico that site.webmanifest and the
// <head> links point at.
//   CHROME_PATH=/path/to/chrome node scripts/favicons.mjs
import { readFileSync, writeFileSync } from 'node:fs'
import puppeteer from 'puppeteer-core'

const svg = readFileSync(new URL('../public/favicon.svg', import.meta.url), 'utf8')
const out = (name) => new URL(`../public/${name}`, import.meta.url).pathname

// apple-touch-icon gets a white background and a margin: iOS does not keep transparency
const icons = [
  { name: 'favicon-16.png', size: 16 },
  { name: 'favicon-32.png', size: 32 },
  { name: 'favicon-48.png', size: 48, temporary: true },
  { name: 'icon-192.png', size: 192 },
  { name: 'icon-512.png', size: 512 },
  { name: 'apple-touch-icon.png', size: 180, background: '#ffffff', padding: 18 },
]

const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH || '/usr/bin/google-chrome', args: ['--no-sandbox'] })
const page = await browser.newPage()
const png = {}
for (const icon of icons) {
  const padding = icon.padding ?? 0
  const inner = icon.size - 2 * padding
  await page.setViewport({ width: icon.size, height: icon.size })
  await page.setContent(`<html><body style="margin:0;background:${icon.background ?? 'transparent'}"><div style="padding:${padding}px">${svg.replace('<svg', `<svg width="${inner}" height="${inner}" style="display:block"`)}</div></body></html>`)
  png[icon.size] = await page.screenshot({ omitBackground: icon.background === undefined })
  if (!icon.temporary) {
    writeFileSync(out(icon.name), png[icon.size])
  }
}
await browser.close()

// favicon.ico with PNG entries (16, 32, 48): a 6-byte header, a 16-byte entry per image, the images
const sizes = [16, 32, 48]
const header = Buffer.alloc(6 + 16 * sizes.length)
header.writeUInt16LE(0, 0)
header.writeUInt16LE(1, 2)
header.writeUInt16LE(sizes.length, 4)
let offset = header.length
sizes.forEach((size, i) => {
  const entry = 6 + 16 * i
  header.writeUInt8(size, entry)
  header.writeUInt8(size, entry + 1)
  header.writeUInt16LE(1, entry + 4)
  header.writeUInt16LE(32, entry + 6)
  header.writeUInt32LE(png[size].length, entry + 8)
  header.writeUInt32LE(offset, entry + 12)
  offset += png[size].length
})
writeFileSync(out('favicon.ico'), Buffer.concat([header, ...sizes.map((size) => png[size])]))
console.log('favicon PNGs and favicon.ico written')
