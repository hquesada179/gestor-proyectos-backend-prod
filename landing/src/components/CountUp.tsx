import { motion, useSpring, useTransform } from "motion/react";
import { useEffect, useRef } from "react";

type CountUpProps = {
  from: number;
  to: number;
  duration?: number;
  decimals?: number;
  suffix?: string;
  prefix?: string;
  className?: string;
  ariaLabel?: string;
};

export function CountUp({
  from,
  to,
  duration = 1.6,
  decimals = 0,
  suffix = "",
  prefix = "",
  className = "",
  ariaLabel,
}: CountUpProps) {
  const ref = useRef<HTMLSpanElement>(null);
  const started = useRef(false);

  const spring = useSpring(from, {
    stiffness: 60,
    damping: 20,
    mass: 1,
  });

  const display = useTransform(spring, (value) => {
    const formatted = value.toLocaleString("es-ES", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
    return `${prefix}${formatted}${suffix}`;
  });

  useEffect(() => {
    const node = ref.current;
    if (!node || started.current) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting && !started.current) {
          started.current = true;
          spring.set(to);
          observer.disconnect();
        }
      },
      { threshold: 0.3 },
    );

    observer.observe(node);
    return () => observer.disconnect();
  }, [spring, to]);

  return (
    <motion.span ref={ref} className={className} aria-label={ariaLabel}>
      {display}
    </motion.span>
  );
}
