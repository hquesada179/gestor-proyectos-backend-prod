import { ArrowRight, Check, ChevronRight, Play } from "lucide-react";
import { motion, useMotionTemplate, useMotionValue, useReducedMotion } from "motion/react";
import { useRef, type MouseEvent } from "react";
import { CountUp } from "./CountUp";
import { ProductMockup } from "./ProductMockup";
import { SIGNUP_URL, content, type Locale } from "../data/content";

export function Hero({ locale }: { locale: Locale }) {
  const copy = content[locale].hero;
  const prefersReduced = useReducedMotion();
  const heroRef = useRef<HTMLElement>(null);
  const x = useMotionValue(-800);
  const y = useMotionValue(-800);
  const spotlight = useMotionTemplate`radial-gradient(600px circle at ${x}px ${y}px, rgba(54,38,206,0.10), transparent 65%)`;

  function onMouseMove(event: MouseEvent<HTMLElement>) {
    const rect = heroRef.current?.getBoundingClientRect();
    if (!rect) return;
    x.set(event.clientX - rect.left);
    y.set(event.clientY - rect.top);
  }

  return (
    <section id="main" ref={heroRef} onMouseMove={onMouseMove} className="hero-section relative overflow-x-clip pb-16 pt-10 md:pb-24 md:pt-16">
      {/* dot grid + washes sutiles + ruido — IA cara */}
      <div className="hero-grid absolute inset-0 -z-20" aria-hidden="true" />
      <div
        className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_82%_12%,rgba(124,58,237,0.05),transparent_28%),radial-gradient(circle_at_16%_8%,rgba(54,38,206,0.05),transparent_26%),linear-gradient(180deg,#fff,rgba(248,250,252,0.65))] dark:bg-[radial-gradient(circle_at_82%_12%,rgba(124,58,237,0.10),transparent_28%),radial-gradient(circle_at_16%_8%,rgba(99,102,241,0.10),transparent_26%),linear-gradient(180deg,#020617,#0f172a)]"
        aria-hidden="true"
      />
      <div className="hero-noise absolute inset-0 -z-10" aria-hidden="true" />
      {/* spotlight que sigue el cursor (desktop, sin reduced-motion) */}
      {!prefersReduced && (
        <motion.div className="pointer-events-none absolute inset-0 -z-10 hidden md:block" style={{ background: spotlight }} aria-hidden="true" />
      )}
      {/* una sola aurora difusa */}
      <div className="hero-aurora" aria-hidden="true" />

      <div className="mx-auto grid max-w-7xl items-center gap-10 px-5 sm:px-6 lg:grid-cols-[1.02fr_1.08fr] lg:gap-8 lg:px-8">
        <div className="min-w-0">
          {/* pill NEW — fiel a referencia con tag */}
          <a href={SIGNUP_URL} className="pill group inline-flex items-center gap-2 !py-1.5 !pr-2 !pl-2.5 text-[0.78rem] !font-semibold hover:border-brand/30">
            <span className="rounded-md bg-brand px-1.5 py-0.5 font-mono text-[0.62rem] font-medium tracking-[0.08em] text-white">{locale === "es" ? "NUEVO" : "NEW"}</span>
            <span className="font-semibold tracking-tight">{copy.badge}</span>
            <ChevronRight size={14} className="opacity-60 transition group-hover:translate-x-0.5 group-hover:opacity-100" aria-hidden="true" />
          </a>

          <h1 className="mt-5 text-balance text-[2.05rem] font-extrabold leading-[1.06] tracking-[-0.03em] text-slate-950 sm:text-5xl lg:text-[3.45rem] dark:text-white">
            {copy.title} <span className="text-brand dark:text-brand-300">{copy.accent}</span>
          </h1>

          <p className="mt-4 max-w-[480px] text-base leading-7 text-slate-500 sm:text-[1.04rem] dark:text-slate-300">{copy.lead}</p>

          <div className="mt-7 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3.5">
            <a className="btn-primary group w-full justify-center !gap-3 !px-3 !py-3 text-[0.95rem] sm:w-auto sm:!pl-6 sm:!pr-3" href={SIGNUP_URL}>
              <span>{copy.primary}</span>
              <span className="grid h-7 w-7 place-items-center rounded-full bg-white text-brand shadow-sm transition group-hover:translate-x-0.5" aria-hidden="true">
                <ArrowRight size={14} strokeWidth={2.7} />
              </span>
            </a>
            <a className="inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-full px-5 text-sm font-semibold text-slate-600 transition hover:text-brand sm:w-auto dark:text-slate-300 dark:hover:text-white" href="#how">
              <span className="grid h-9 w-9 place-items-center rounded-full border-2 border-brand text-brand dark:border-brand-300 dark:text-brand-200" aria-hidden="true">
                <Play size={14} fill="currentColor" className="ml-0.5" />
              </span>
              {copy.secondary}
            </a>
          </div>

          <div className="mt-7 flex flex-wrap gap-4 sm:gap-5">
            {copy.checks.map((check) => (
              <span key={check} className="flex items-center gap-2 text-[0.84rem] font-medium text-slate-600 dark:text-slate-300">
                <span className="grid h-[18px] w-[18px] place-items-center rounded-full bg-brand text-white" aria-hidden="true">
                  <Check size={11} strokeWidth={3} />
                </span>
                {check}
              </span>
            ))}
          </div>

          {/* métricas visibles también en móvil, como referencia bottom row */}
          <div className="mt-9 grid max-w-xl grid-cols-3 gap-4 sm:gap-6">
            {copy.metrics.map((metric) => {
              const raw = metric.value.replace(/[^\d.,]/g, "").replace(",", ".");
              const num = parseFloat(raw);
              const decimals = raw.includes(".") ? 1 : 0;
              const suffix = metric.value.replace(/[\d.,]/g, "");
              return (
                <div key={metric.label} className="border-l-[3px] border-brand/20 pl-3 sm:pl-4">
                  <b className="block text-[1.22rem] font-extrabold tracking-[-0.02em] text-slate-950 tabular-nums sm:text-2xl dark:text-white">
                    <CountUp from={0} to={num} decimals={decimals} suffix={suffix} ariaLabel={`${metric.value} ${metric.label}`} />
                  </b>
                  <span className="mt-1 block text-[11px] font-semibold leading-4 text-slate-500 dark:text-slate-400">{metric.label}</span>
                </div>
              );
            })}
          </div>
        </div>

        <div className="relative mx-auto w-full max-w-[600px] lg:ml-auto lg:max-w-[640px]">
          {/* borde gradient animado + perspectiva mockup */}
          <div className="hero-mockup-wrap">
            <div className="gradient-beam rounded-[18px]">
              <ProductMockup locale={locale} />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
