type SealEmblemProps = {
  size?: number
  className?: string
  glow?: boolean
  eager?: boolean
  alt?: string
}

export function SealEmblem({
  size = 56,
  className = '',
  glow = true,
  eager = false,
  alt = '',
}: SealEmblemProps) {
  return (
    <span
      className={`relative inline-flex shrink-0 items-center justify-center ${className}`}
      style={{ width: size, height: size }}
    >
      {glow ? (
        <span
          aria-hidden="true"
          className="absolute inset-0 rounded-full bg-gold/30 blur-xl"
        />
      ) : null}
      <img
        src="/icons/icon-512.png"
        alt={alt}
        width={size}
        height={size}
        loading={eager ? 'eager' : 'lazy'}
        decoding="async"
        draggable={false}
        className="relative h-full w-full rounded-full object-cover ring-1 ring-gold/60"
      />
    </span>
  )
}
