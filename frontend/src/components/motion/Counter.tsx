import { animate, useInView, useReducedMotion } from 'framer-motion'
import { useEffect, useRef, useState } from 'react'

const defaultFormat = new Intl.NumberFormat('id-ID').format

type CounterProps = {
  value: number
  format?: (value: number) => string
  className?: string
  duration?: number
}

export function Counter({ value, format = defaultFormat, className, duration = 1.1 }: CounterProps) {
  const ref = useRef<HTMLSpanElement>(null)
  const inView = useInView(ref, { once: true, amount: 0.45 })
  const reduce = useReducedMotion()
  const [display, setDisplay] = useState(0)

  useEffect(() => {
    if (!inView || reduce) {
      return
    }

    const controls = animate(0, value, {
      duration,
      ease: [0.16, 1, 0.3, 1],
      onUpdate: (latest) => setDisplay(Math.round(latest)),
    })

    return () => controls.stop()
  }, [duration, inView, reduce, value])

  return (
    <span ref={ref} className={className} aria-label={format(value)}>
      <span aria-hidden="true">{format(reduce ? value : display)}</span>
    </span>
  )
}
