import { ArrowRight, ArrowUpRight, ShieldCheck, Sparkles } from "lucide-react";
import { SIGNUP_URL, content, iconMap, type Locale } from "../data/content";
import { Reveal } from "./Reveal";
import { SectionHeading } from "./SectionHeading";

/* Bento grid: 2 destacadas medias + 3 compactas + 1 ancha de cierre (lg:grid-cols-6). */
const spanClasses = ["lg:col-span-3", "lg:col-span-3", "lg:col-span-2", "lg:col-span-2", "lg:col-span-2", "lg:col-span-6"] as const;

export function Features({ locale }: { locale: Locale }) {
  const copy = content[locale].features;

  return (
    <section id="features" className="border-y border-slate-200 bg-slate-50 py-24 md:py-28 dark:border-white/10 dark:bg-white/[0.03]">
      <div className="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <SectionHeading eyebrow={copy.eyebrow} title={copy.title} accent={copy.accent} subtitle={copy.subtitle} />
        <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-6">
          {copy.items.map((item, index) => {
            const Icon = iconMap[item.icon];
            const featured = index === 0 || index === 1;
            const wide = index === 5;
            return (
              <Reveal key={item.title} delay={index * 60} className={`${spanClasses[index]} h-full`}>
                <article className={`feature-card group flex h-full flex-col ${wide ? "md:flex-row md:items-center md:justify-between md:gap-8" : ""}`}>
                  <div className={wide ? "md:max-w-xl" : ""}>
                    <div className={`flex items-center justify-center rounded-2xl bg-white text-brand shadow-sm ring-1 ring-slate-200 transition group-hover:bg-brand group-hover:text-white dark:bg-slate-950 dark:text-brand-300 dark:ring-white/10 ${featured ? "h-12 w-12" : "h-11 w-11"}`}>
                      <Icon size={featured ? 22 : 20} aria-hidden="true" />
                    </div>
                    <h3 className={`font-black text-slate-950 dark:text-white ${featured ? "mt-5 text-2xl" : "mt-5 text-xl"}`}>{item.title}</h3>
                    <p className={`text-slate-600 dark:text-slate-300 ${featured ? "mt-2.5 text-[15px] leading-7" : "mt-3 text-sm leading-7"}`}>{item.text}</p>
                    <a
                      className="mt-4 inline-flex min-h-11 items-center gap-2 text-sm font-black text-brand transition hover:gap-3 dark:text-brand-300"
                      href={wide ? "#pricing" : SIGNUP_URL}
                      aria-label={`${copy.learnMorePrefix} ${item.title}`}
                    >
                      {copy.learnMore}
                      <ArrowUpRight size={16} aria-hidden="true" />
                    </a>
                  </div>

                  {/* Mini-visuales decorativos para equilibrar las tarjetas destacadas. */}
                  {featured && (
                    <div className="mt-6 flex-1" aria-hidden="true">
                      {index === 0 ? (
                        <div className="grid gap-2 rounded-2xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-sm dark:border-white/[0.06] dark:bg-white/[0.04]">
                          {[0, 1, 2].map((row) => (
                            <div key={row} className="flex items-center gap-2.5">
                              <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-brand/70 dark:bg-brand-300" />
                              <span className="h-2 rounded-full bg-slate-200 dark:bg-white/10" style={{ width: `${68 - row * 14}%` }} />
                              <ArrowRight size={12} className="ml-auto shrink-0 text-slate-400 dark:text-slate-500" />
                              <span className="h-2 w-10 shrink-0 rounded-full bg-brand/20 dark:bg-brand-300/30" />
                            </div>
                          ))}
                        </div>
                      ) : (
                        <div className="flex items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-sm dark:border-white/[0.06] dark:bg-white/[0.04]">
                          <Sparkles size={14} className="shrink-0 text-brand dark:text-brand-300" />
                          <span className="font-mono text-[11px] tracking-tight text-slate-600 dark:text-slate-300">
                            {copy.aiPromptSample}
                          </span>
                          <span className="ml-auto h-4 w-1.5 shrink-0 animate-pulse rounded-full bg-brand/60 dark:bg-brand-300/70" />
                        </div>
                      )}
                    </div>
                  )}

                  {/* Panel lateral en la tarjeta ancha para mostrar métodos Wompi sin espacio muerto. */}
                  {wide && (
                    <div className="mt-6 flex flex-wrap items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-white/80 p-4 md:mt-0 md:max-w-sm md:justify-end dark:border-white/10 dark:bg-white/[0.04]" aria-hidden="true">
                      <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <ShieldCheck size={14} />
                        Wompi
                      </span>
                      {copy.wompiMethods.map((method) => (
                        <span
                          key={method}
                          className="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-200"
                        >
                          {method}
                        </span>
                      ))}
                    </div>
                  )}
                </article>
              </Reveal>
            );
          })}
        </div>
      </div>
    </section>
  );
}
