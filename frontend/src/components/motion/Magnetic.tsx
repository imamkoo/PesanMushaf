import { m, useMotionValue, useReducedMotion, useSpring } from 'framer-motion'
import type { PointerEvent, ReactNode } from 'react'
import { useMediaQuery } from '../../hooks/useMediaQuery'

type MagneticProps = {
  children: ReactNode
  className?: string
  strength?: number
}

export function Magnetic({ children, className = '', strength = 0.18 }: MagneticProps) {
  const reduce = useReducedMotion()
  const canHover = useMediaQuery('(hover: hover) and (pointer: fine)')
  const active = canHover && !reduce
  const x = useMotionValue(0)
  const y = useMotionValue(0)
  const springX = useSpring(x, { stiffness: 260, damping: 20, mass: 0.4 })
  const springY = useSpring(y, { stiffness: 260, damping: 20, mass: 0.4 })

  function handlePointerMove(event: PointerEvent<HTMLDivElement>) {
    if (!active) {
      return
    }

    const bounds = event.currentTarget.getBoundingClientRect()
    x.set((event.clientX - bounds.left - bounds.width / 2) * strength)
    y.set((event.clientY - bounds.top - bounds.height / 2) * strength)
  }

  function handlePointerLeave() {
    x.set(0)
    y.set(0)
  }

  return (
    <m.div
      className={`inline-flex ${className}`}
      onPointerMove={handlePointerMove}
      onPointerLeave={handlePointerLeave}
      style={{ x: springX, y: springY }}
    >
      {children}
    </m.div>
  )
}
