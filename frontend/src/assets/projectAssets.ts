const image = (path: string) => `/assets/images/${path}`

export const icons = {
  call: image('icons/call.svg'),
  crown: image('icons/crown-white.svg'),
  explore: image('icons/slider-horizontal-white.svg'),
  video: image('icons/video-octagon.svg'),
  securityUser: image('icons/security-user.svg'),
  group: image('icons/group.svg'),
  cube: image('icons/3dcube.svg'),
  cup: image('icons/cup.svg'),
  coffee: image('icons/coffee.svg'),
  homeTrendUp: image('icons/home-trend-up.svg'),
  clock: image('icons/clock.svg'),
  location: image('icons/location.svg'),
  star: image('icons/Star 1.svg'),
  wifi: image('icons/wifi.svg'),
}

export const photos = {
  hero: image('backgrounds/banner.png'),
  districts: [
    image('thumbnails/thumbnails-2.png'),
    image('thumbnails/thumbnails-1.png'),
    image('thumbnails/thumbnails-3.png'),
    image('thumbnails/thumbnails-4.png'),
    image('thumbnails/thumbnails-5.png'),
    image('thumbnails/thumbnails-6.png'),
    image('thumbnails/thumbnails-7.png'),
  ],
  batches: [
    image('thumbnails/thumbnails-1.png'),
    image('thumbnails/thumbnails-3.png'),
    image('thumbnails/thumbnails-4.png'),
    image('thumbnails/thumbnails-5.png'),
    image('thumbnails/thumbnails-6.png'),
    image('thumbnails/thumbnails-2.png'),
  ],
}
