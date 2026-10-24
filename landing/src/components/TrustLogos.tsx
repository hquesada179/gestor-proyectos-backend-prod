import { ShieldCheck, Star, Users } from "lucide-react";
import { content, type Locale } from "../data/content";

const badgeIcons = [Star, Users, ShieldCheck];

export function TrustLogos({ locale }: { locale: Locale }) {
  const copy = content[locale].trust;
  const logos = [...copy.logos, ...copy.logos]; // duplicado para loop infinito

  return (
    <section className="border-y border-slate-200 bg-slate-50 py-10 dark:border-white/10 dark:bg-white/[0.03]" aria-label={copy.eyebrow}>
      <div className="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <ul className="mb-6 flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
          {copy.badges.map((badge, index) => {
            const Icon = badgeIcons[index % badgeIcons.length];
            return (
              <li key={badge} className="flex items-center gap-2 font-mono text-[11px] font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                <Icon size={13} className={index === 0 ? "text-amber-500" : "text-brand dark:text-brand-400"} aria-hidden="true" />
                {badge}
              </li>
            );
          })}
        </ul>
        <p className="text-center text-[11px] font-black uppercase tracking-widest text-slate-400 sm:text-xs sm:tracking-[0.18em] dark:text-slate-500">
          {copy.eyebrow}
        </p>
        <div className="marquee-wrapper mt-8">
          <ul className="marquee-track flex gap-6">
            {logos.map((logo, index) => (
              <li
                key={`${logo}-${index}`}
                className="flex min-h-16 min-w-[140px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 text-sm font-black text-slate-400 grayscale transition hover:text-slate-700 hover:grayscale-0 dark:border-white/10 dark:bg-slate-950/50 dark:text-slate-500 dark:hover:text-white"
                aria-hidden={index >= copy.logos.length}
              >
                {logo}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </section>
  );
}
