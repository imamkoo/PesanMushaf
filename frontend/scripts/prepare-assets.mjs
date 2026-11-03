import { mkdir, stat } from 'node:fs/promises'
import path from 'node:path'
import sharp from 'sharp'

const root = process.cwd()
const bannerSource = path.join(root, 'scripts/assets/banner.png')
const bannerTarget = path.join(root, 'public/assets/images/backgrounds/banner.webp')
const thumbnailSourceDir = path.join(root, 'scripts/assets/thumbnails')
const thumbnailTargetDir = path.join(root, 'public/assets/images/thumbnails')
const thumbnailNames = Array.from({ length: 7 }, (_, index) => `thumbnails-${index + 1}`)

async function writeBannerWebp() {
  await sharp(bannerSource)
    .resize({ width: 1200, withoutEnlargement: true })
    .webp({ quality: 80, effort: 6 })
    .toFile(bannerTarget)
}

async function writeThumbnailsWebp() {
  await mkdir(thumbnailTargetDir, { recursive: true })

  await Promise.all(
    thumbnailNames.map((name) =>
      sharp(path.join(thumbnailSourceDir, `${name}.png`))
        .resize({ width: 460, withoutEnlargement: true })
        .webp({ quality: 80, effort: 6 })
        .toFile(path.join(thumbnailTargetDir, `${name}.webp`)),
    ),
  )
}

await Promise.all([writeBannerWebp(), writeThumbnailsWebp()])

for (const target of [
  bannerTarget,
  ...thumbnailNames.map((name) => path.join(thumbnailTargetDir, `${name}.webp`)),
]) {
  const { size } = await stat(target)
  console.log(`${path.relative(root, target)} — ${(size / 1024).toFixed(0)} KB`)
}
