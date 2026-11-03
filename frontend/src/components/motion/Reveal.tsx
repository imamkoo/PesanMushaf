import { m, useReducedMotion } from 'framer-motion'
import type { ReactNode } from 'react'

type RevealProps = {
  children: ReactNode
  className?: string
  id?: string
  delay?: number
  y?: number
  once?: boolean
  amount?: number
}

export function Reveal({
  children,
  className,
  id,
  delay = 0,
  y = 18,
  once = true,
  amount = 0.2,
}: RevealProps) {
  const reduce = useReducedMotion()

  return (
    <m.div
      id={id}
      className={className}
      initial={reduce ? false : { opacity: 0, y }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once, amount, margin: '0px 0px -8% 0px' }}
      transition={{ duration: 0.72, ease: [0.16, 1, 0.3, 1], delay }}
    >
      {children}
    </m.div>
  )
}
