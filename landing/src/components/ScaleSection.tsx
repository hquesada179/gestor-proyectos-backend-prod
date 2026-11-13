import { ArrowRight, Check } from "lucide-react";
import type { ReactNode } from "react";
import { SIGNUP_URL, content, iconMap, type Locale } from "../data/content";
import { CountUp } from "./CountUp";
import { Reveal } from "./Reveal";

export function ScaleSection({ locale }: { locale: Locale }) {
  const copy = content[locale].scale;

  return (
    <section id="scale" className="py-24 md:py-28">
      <div className="mx-auto grid max-w-7xl items-center gap-12 px-5 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
        <Reveal>
          <div>
            <span className="pill">{copy.eyebrow}</span>
            <h2 className="mt-6 text-balance text-3xl font-black tracking-normal text-slate-950 md:text-5xl dark:text-white">
              {copy.title}
            </h2>
            <p className="mt-5 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300">{copy.text}</p>
            <div className="mt-8 grid gap-3 sm:grid-cols-2">
              {copy.checks.map((check) => (
                <span key={check} className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                  <Check size={16} className="shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                  {check}
                </span>
              ))}
            </div>
            <a className="btn-primary group mt-8" href={SIGNUP_URL}>
              {copy.cta}
              <ArrowRight size={18} className="transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
            </a>
          </div>
        </Reveal>

        <Reveal delay={100}>
          <div className="grid gap-4 sm:grid-cols-2">
            {copy.metrics.map((metric) => {
              const Icon = iconMap[metric.icon];
              return (
                <MetricTile
                  key={metric.label}
                  icon={<Icon aria-hidden="true" />}
                  label={metric.label}
                  to={metric.to}
                  decimals={metric.decimals}
                  prefix={metric.prefix}
                  suffix={metric.suffix}
                  display={metric.display}
                  locale={locale}
                  tone={metric.tone}
                />
              );
            })}
          </div>
        </Reveal>
      </div>
    </section>
  );
}

function MetricTile({
  icon,
  label,
  to,
  decimals,
  prefix,
  suffix,
  display,
  locale,
  tone,
}: {
  icon: ReactNode;
  label: string;
  to: number;
  decimals: number;
  prefix: string;
  suffix: string;
  display: string;
  locale: Locale;
  tone: "brand" | "amber" | "emerald" | "slate";
}) {
  const toneClasses = {
    brand: "bg-brand/10 text-brand dark:bg-brand-400/15 dark:text-brand-200",
    amber: "bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-200",
    emerald: "bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200",
    slate: "bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-white",
  };

  return (
    <article className="feature-card">
      <div className={`flex h-12 w-12 items-center justify-center rounded-2xl ${toneClasses[tone]}`}>{icon}</div>
      {/* tabular-nums evita saltos de ancho horizontal durante la interpolación del contador. */}
      <b className="mt-7 block text-4xl font-black tabular-nums text-slate-950 dark:text-white">
        <CountUp from={0} to={to} decimals={decimals} prefix={prefix} suffix={suffix} locale={locale} ariaLabel={`${display} ${label}`} />
      </b>
      <span className="mt-2 block text-sm font-bold text-slate-600 dark:text-slate-300">{label}</span>
    </article>
  );
}
