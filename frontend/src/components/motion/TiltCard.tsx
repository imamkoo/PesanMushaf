import { m, useMotionTemplate, useMotionValue, useReducedMotion, useSpring } from 'framer-motion'
import type { PointerEvent, ReactNode } from 'react'
import { useMediaQuery } from '../../hooks/useMediaQuery'

type TiltCardProps = {
  children: ReactNode
  className?: string
  tilt?: number
  spotlight?: boolean
  spotlightColor?: string
}

export function TiltCard({
  children,
  className = '',
  tilt = 4,
  spotlight = true,
  spotlightColor = 'rgba(216, 180, 90, 0.16)',
}: TiltCardProps) {
  const reduce = useReducedMotion()
  const canHover = useMediaQuery('(hover: hover) and (pointer: fine)')
  const active = canHover && !reduce

  const rotateX = useMotionValue(0)
  const rotateY = useMotionValue(0)
  const springX = useSpring(rotateX, { stiffness: 180, damping: 22, mass: 0.45 })
  const springY = useSpring(rotateY, { stiffness: 180, damping: 22, mass: 0.45 })
  const pointerX = useMotionValue(50)
  const pointerY = useMotionValue(50)
  const spotlightBackground = useMotionTemplate`radial-gradient(420px circle at ${pointerX}% ${pointerY}%, ${spotlightColor}, transparent 48%)`

  function handlePointerMove(event: PointerEvent<HTMLDivElement>) {
    if (!active) {
      return
    }

    const bounds = event.currentTarget.getBoundingClientRect()
    const x = (event.clientX - bounds.left) / bounds.width
    const y = (event.clientY - bounds.top) / bounds.height

    rotateY.set((x - 0.5) * tilt * 2)
    rotateX.set((0.5 - y) * tilt * 2)
    pointerX.set(x * 100)
    pointerY.set(y * 100)
  }

  function handlePointerLeave() {
    rotateX.set(0)
    rotateY.set(0)
    pointerX.set(50)
    pointerY.set(50)
  }

  return (
    <m.div
      className={`group relative ${className}`}
      onPointerMove={handlePointerMove}
      onPointerLeave={handlePointerLeave}
      style={{
        rotateX: springX,
        rotateY: springY,
        transformPerspective: 1100,
        transformStyle: 'preserve-3d',
      }}
    >
      {children}
      {spotlight && active ? (
        <m.div
          aria-hidden="true"
          className="pointer-events-none absolute inset-0 z-20 rounded-[inherit] opacity-0 mix-blend-soft-light transition-opacity duration-300 group-hover:opacity-100"
          style={{ background: spotlightBackground }}
        />
      ) : null}
    </m.div>
  )
}
