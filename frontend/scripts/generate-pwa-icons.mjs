// Generator ikon PWA, favicon, dan apple-touch-icon dari logo Arsip Abadi Jakarta 500 Tahun.
// Jalankan: `node scripts/generate-pwa-icons.mjs` (sekali jalan; commit hasilnya).
import sharp from 'sharp'
import { mkdir, writeFile } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const projectRoot = resolve(__dirname, '..')
const source = resolve(__dirname, 'assets/logo-seal.jpeg')
const iconsDir = resolve(projectRoot, 'public/icons')
const publicDir = resolve(projectRoot, 'public')

const background = '#ffffff'
const pngOptions = { compressionLevel: 9, palette: true, quality: 92, effort: 10 }

async function tightLogo(size) {
  const trimmed = await sharp(source)
    .trim({ background, threshold: 14 })
    .toBuffer()

  return sharp(trimmed)
    .resize(size, size, { fit: 'contain', background })
    .flatten({ background })
    .png(pngOptions)
    .toBuffer()
}

async function writeTight(size, outputPath) {
  await mkdir(dirname(outputPath), { recursive: true })
  await writeFile(outputPath, await tightLogo(size))
  console.log('wrote', outputPath)
}

async function writeMaskable(size, outputPath) {
  const innerSize = Math.round(size * 0.76)
  const inner = await tightLogo(innerSize)
  const canvas = sharp({
    create: { width: size, height: size, channels: 3, background },
  })

  await mkdir(dirname(outputPath), { recursive: true })
  await canvas
    .composite([{ input: inner, gravity: 'center' }])
    .png(pngOptions)
    .toFile(outputPath)
  console.log('wrote', outputPath)
}

async function main() {
  await mkdir(iconsDir, { recursive: true })

  await writeTight(192, resolve(iconsDir, 'icon-192.png'))
  await writeTight(512, resolve(iconsDir, 'icon-512.png'))
  await writeTight(1024, resolve(iconsDir, 'icon-1024.png'))
  await writeMaskable(512, resolve(iconsDir, 'maskable-512.png'))
  await writeTight(180, resolve(publicDir, 'apple-touch-icon.png'))
  await writeTight(32, resolve(publicDir, 'favicon-32.png'))

  console.log('done')
}

main().catch((err) => {
  console.error(err)
  process.exit(1)
})
