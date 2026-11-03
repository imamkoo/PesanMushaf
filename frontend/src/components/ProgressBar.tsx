import { m, useReducedMotion } from 'framer-motion'

type ProgressTone = 'brand' | 'ink' | 'amber'

type ProgressBarProps = {
  value: number
  tone?: ProgressTone
  className?: string
  label?: string
}

const toneClass: Record<ProgressTone, string> = {
  brand: 'bg-brand',
  ink: 'bg-ink',
  amber: 'bg-amber-500',
}

export function ProgressBar({ value, tone = 'brand', className = '', label }: ProgressBarProps) {
  const reduce = useReducedMotion()
  const clamped = Math.max(0, Math.min(100, value))

  return (
    <div
      role="progressbar"
      aria-valuenow={clamped}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-label={label ?? `Kuota terisi ${clamped} persen`}
      className={`h-1.5 w-full overflow-hidden rounded-full bg-black/[0.07] ${className}`}
    >
      <m.div
        className={`h-full rounded-full ${toneClass[tone]}`}
        initial={reduce ? { width: `${clamped}%` } : { width: 0 }}
        whileInView={{ width: `${clamped}%` }}
        viewport={{ once: true, amount: 0.6 }}
        transition={reduce ? { duration: 0 } : { duration: 0.9, ease: [0.16, 1, 0.3, 1] }}
      />
    </div>
  )
}
