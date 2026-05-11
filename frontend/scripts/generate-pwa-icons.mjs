// Generator placeholder icon HUT500 PWA.
// Jalankan: `node scripts/generate-pwa-icons.mjs` (sekali jalan; commit hasilnya).
// Ganti dengan logo final kapan saja — cukup replace 4 file PNG di public/icons + public/apple-touch-icon.png.
import sharp from 'sharp'
import { mkdir } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const projectRoot = resolve(__dirname, '..')
const iconsDir = resolve(projectRoot, 'public/icons')
const publicDir = resolve(projectRoot, 'public')

const brandRed = '#ed3833'
const brandDark = '#111111'
const safeRatio = 0.8 // for maskable safe-area

function tightSvg(size) {
  const radius = Math.round(size * 0.22)
  const fontSize = Math.round(size * 0.46)
  const accentSize = Math.round(size * 0.16)
  const accentY = Math.round(size * 0.78)
  const accentX = Math.round(size * 0.5)

  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
  <rect width="${size}" height="${size}" rx="${radius}" ry="${radius}" fill="${brandDark}" />
  <text x="50%" y="50%" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" font-weight="900" font-size="${fontSize}" letter-spacing="-${Math.round(fontSize * 0.06)}" fill="#ffffff" text-anchor="middle" dominant-baseline="middle" dy="0.02em">H5</text>
  <circle cx="${accentX}" cy="${accentY}" r="${accentSize / 2}" fill="${brandRed}" />
</svg>`
}

function maskableSvg(size) {
  // For maskable icons, content lives inside safe-area (~80% of canvas).
  // Background bleeds full-canvas with brand red so the OS-imposed mask never reveals empty edges.
  const safeSize = Math.round(size * safeRatio)
  const safeOffset = Math.round((size - safeSize) / 2)
  const radius = Math.round(safeSize * 0.22)
  const fontSize = Math.round(safeSize * 0.46)

  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
  <rect width="${size}" height="${size}" fill="${brandRed}" />
  <rect x="${safeOffset}" y="${safeOffset}" width="${safeSize}" height="${safeSize}" rx="${radius}" ry="${radius}" fill="${brandDark}" />
  <text x="50%" y="50%" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" font-weight="900" font-size="${fontSize}" letter-spacing="-${Math.round(fontSize * 0.06)}" fill="#ffffff" text-anchor="middle" dominant-baseline="middle" dy="0.02em">H5</text>
</svg>`
}

async function writeIcon(svgString, outputPath) {
  const buffer = Buffer.from(svgString)
  await mkdir(dirname(outputPath), { recursive: true })
  await sharp(buffer).png({ compressionLevel: 9 }).toFile(outputPath)
  console.log('wrote', outputPath)
}

async function main() {
  await mkdir(iconsDir, { recursive: true })

  await writeIcon(tightSvg(192), resolve(iconsDir, 'icon-192.png'))
  await writeIcon(tightSvg(512), resolve(iconsDir, 'icon-512.png'))
  await writeIcon(maskableSvg(512), resolve(iconsDir, 'maskable-512.png'))
  await writeIcon(tightSvg(180), resolve(publicDir, 'apple-touch-icon.png'))

  console.log('done')
}

main().catch((err) => {
  console.error(err)
  process.exit(1)
})
