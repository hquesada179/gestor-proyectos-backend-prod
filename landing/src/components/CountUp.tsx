import { motion, useReducedMotion, useSpring, useTransform } from "motion/react";
import { useEffect, useRef } from "react";
import type { Locale } from "../data/content";

type CountUpProps = {
  from: number;
  to: number;
  decimals?: number;
  suffix?: string;
  prefix?: string;
  locale?: Locale;
  className?: string;
  ariaLabel?: string;
};

/** Formatea un número evitando errores de separadores entre español e inglés. */
function formatCountValue(value: number, decimals: number, locale: Locale): string {
  const numberLocale = locale === "es" ? "es-CO" : "en-US";
  return value.toLocaleString(numberLocale, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

export function CountUp({
  from,
  to,
  decimals = 0,
  suffix = "",
  prefix = "",
  locale = "es",
  className = "",
  ariaLabel,
}: CountUpProps) {
  const ref = useRef<HTMLSpanElement>(null);
  const started = useRef(false);
  const prefersReduced = useReducedMotion();

  const spring = useSpring(from, {
    stiffness: 60,
    damping: 20,
    mass: 1,
  });

  const display = useTransform(spring, (value) => {
    return `${prefix}${formatCountValue(value, decimals, locale)}${suffix}`;
  });

  useEffect(() => {
    // No observamos intersección ni animamos si el usuario prefiere movimiento reducido.
    if (prefersReduced) return;

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
  }, [prefersReduced, spring, to]);

  // Muestra el valor definitivo sin transición cuando prefers-reduced-motion está activo.
  if (prefersReduced) {
    return (
      <span ref={ref} className={className} aria-label={ariaLabel}>
        {`${prefix}${formatCountValue(to, decimals, locale)}${suffix}`}
      </span>
    );
  }

  return (
    <motion.span ref={ref} className={className} aria-label={ariaLabel}>
      {display}
    </motion.span>
  );
}
