import { stat } from 'node:fs/promises'
import path from 'node:path'
import sharp from 'sharp'

const root = process.cwd()
const bannerSource = path.join(root, 'scripts/assets/banner.png')
const bannerTarget = path.join(root, 'public/assets/images/backgrounds/banner.webp')

async function writeBannerWebp() {
  await sharp(bannerSource)
    .resize({ width: 1600, withoutEnlargement: true })
    .webp({ quality: 80, effort: 6 })
    .toFile(bannerTarget)
}

await writeBannerWebp()

const { size } = await stat(bannerTarget)
console.log(`${path.relative(root, bannerTarget)} — ${(size / 1024).toFixed(0)} KB`)
