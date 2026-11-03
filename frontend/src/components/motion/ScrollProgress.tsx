import { m, useReducedMotion, useScroll, useSpring } from 'framer-motion'

export function ScrollProgress() {
  const { scrollYProgress } = useScroll()
  const reduce = useReducedMotion()
  const smooth = useSpring(scrollYProgress, { stiffness: 140, damping: 30, restDelta: 0.001 })

  return (
    <m.div
      aria-hidden="true"
      className="pointer-events-none fixed inset-x-0 top-0 z-[80] h-[2px] origin-left bg-[linear-gradient(90deg,#ed3833,#f08a5d_48%,#d8b45a)]"
      style={{ scaleX: reduce ? scrollYProgress : smooth }}
    />
  )
}
