import { defineConfig } from 'vite'
import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import babel from '@rolldown/plugin-babel'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    tailwindcss(),
    react(),
    babel({ presets: [reactCompilerPreset()] }),
    VitePWA({
      registerType: 'prompt',
      injectRegister: false,
      includeAssets: [
        'favicon.svg',
        'apple-touch-icon.png',
        'icons/icon-192.png',
        'icons/icon-512.png',
        'icons/maskable-512.png',
      ],
      manifest: {
        name: 'HUT500 Jakarta',
        short_name: 'HUT500',
        description: 'Pendaftaran Mushaf Jakarta 500 — kontribusi nyata untuk Jakarta.',
        lang: 'id',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        background_color: '#f4f0ea',
        theme_color: '#ed3833',
        orientation: 'portrait',
        categories: ['social', 'lifestyle', 'productivity'],
        icons: [
          { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
          { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
          { src: '/icons/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        navigateFallback: '/index.html',
        navigateFallbackDenylist: [/^\/api\//],
        globPatterns: ['**/*.{js,css,html,svg,png,webp,woff2}'],
        // Banner & thumbnail-details sangat besar (>2 MiB default Workbox) — tidak di-precache.
        // Tetap tersedia lewat runtime CacheFirst untuk /assets/images/... di bawah.
        globIgnores: [
          '**/assets/images/backgrounds/banner.png',
          '**/assets/images/thumbnails/thumbnail-details-*.png',
        ],
        cleanupOutdatedCaches: true,
        clientsClaim: true,
        runtimeCaching: [
          {
            urlPattern: ({ url }) => /\/api\/(districts|batches|price-categories|universities)/.test(url.pathname),
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'hut500-catalog',
              expiration: { maxEntries: 60, maxAgeSeconds: 60 * 60 * 24 * 7 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          {
            urlPattern: ({ url }) => /\/api\/school-options(\/?$|\?)/.test(url.pathname + url.search),
            handler: 'NetworkFirst',
            options: {
              cacheName: 'hut500-schools',
              networkTimeoutSeconds: 4,
              expiration: { maxEntries: 60, maxAgeSeconds: 60 * 60 * 24 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          {
            urlPattern: /\/assets\/images\/.*\.(png|svg|webp|jpg|jpeg)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'hut500-images',
              expiration: { maxEntries: 80, maxAgeSeconds: 60 * 60 * 24 * 30 },
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          {
            urlPattern: ({ url }) => /\/api\/(register|midtrans|registrations\/status|school-options\/match)/.test(url.pathname),
            handler: 'NetworkOnly',
          },
        ],
      },
      devOptions: { enabled: false },
    }),
  ],
})
