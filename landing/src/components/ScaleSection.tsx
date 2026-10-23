import { ArrowRight, Check, Gauge, ShieldCheck, UsersRound, Zap } from "lucide-react";
import type { ReactNode } from "react";
import { SIGNUP_URL, content, type Locale } from "../data/content";
import { CountUp } from "./CountUp";
import { Reveal } from "./Reveal";

export function ScaleSection({ locale }: { locale: Locale }) {
  const copy = content[locale].scale;

  return (
    <section className="py-24 md:py-28">
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
                  <Check size={16} className="text-emerald-500" />
                  {check}
                </span>
              ))}
            </div>
            <a className="btn-primary mt-8" href={SIGNUP_URL}>
              {copy.cta}
              <ArrowRight size={18} />
            </a>
          </div>
        </Reveal>

        <Reveal delay={100}>
          <div className="grid gap-4 sm:grid-cols-2">
            <MetricTile
              icon={<UsersRound aria-hidden="true" />}
              label={locale === "es" ? "Usuarios" : "Users"}
              value="15+"
              tone="brand"
            />
            <MetricTile icon={<Zap aria-hidden="true" />} label={locale === "es" ? "Créditos IA" : "AI credits"} value="5k" tone="amber" />
            <MetricTile
              icon={<Gauge aria-hidden="true" />}
              label={locale === "es" ? "Velocidad sprint" : "Sprint velocity"}
              value="+31%"
              tone="emerald"
            />
            <MetricTile
              icon={<ShieldCheck aria-hidden="true" />}
              label={locale === "es" ? "Auditoría" : "Audit trail"}
              value="100%"
              tone="slate"
            />
          </div>
        </Reveal>
      </div>
    </section>
  );
}

function MetricTile({
  icon,
  label,
  value,
  tone,
}: {
  icon: ReactNode;
  label: string;
  value: string;
  tone: "brand" | "amber" | "emerald" | "slate";
}) {
  const toneClasses = {
    brand: "bg-brand/10 text-brand dark:bg-brand-400/12 dark:text-brand-200",
    amber: "bg-amber-100 text-amber-700 dark:bg-amber-400/12 dark:text-amber-200",
    emerald: "bg-emerald-100 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-200",
    slate: "bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-white",
  };

  const raw = value.replace(/[^\d.,]/g, "").replace(",", ".");
  const num = parseFloat(raw);
  const decimals = raw.includes(".") ? 1 : 0;
  const suffix = value.replace(/[\d.,]/g, "");
  const prefix = value.startsWith("+") ? "+" : "";

  return (
    <div className="min-h-44 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
      <div className={`flex h-12 w-12 items-center justify-center rounded-2xl ${toneClasses[tone]}`}>{icon}</div>
      <b className="mt-7 block text-4xl font-black text-slate-950 dark:text-white">
        <CountUp from={0} to={num} decimals={decimals} suffix={suffix} prefix={prefix} ariaLabel={`${value} ${label}`} />
      </b>
      <span className="mt-2 block text-sm font-bold text-slate-500 dark:text-slate-400">{label}</span>
    </div>
  );
}
