import { ArrowRight, ArrowUpRight, Sparkles } from "lucide-react";
import { SIGNUP_URL, content, iconMap, type Locale } from "../data/content";
import { Reveal } from "./Reveal";
import { SectionHeading } from "./SectionHeading";

/* bento IA cara: destacadas medias + normales + ancha al final (lg:grid-cols-6) */
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
              <Reveal key={item.title} delay={index * 60} className={spanClasses[index]}>
                <article className={`feature-card group flex h-full flex-col ${wide ? "md:flex-row md:items-center md:gap-8" : ""}`}>
                  <div className={wide ? "md:max-w-xl" : ""}>
                    <div className={`flex items-center justify-center rounded-2xl bg-white text-brand shadow-sm ring-1 ring-slate-200 transition group-hover:bg-brand group-hover:text-white dark:bg-slate-950 dark:ring-white/10 ${featured ? "h-12 w-12" : "h-11 w-11"}`}>
                      <Icon size={featured ? 22 : 20} aria-hidden="true" />
                    </div>
                    <h3 className={`font-black text-slate-950 dark:text-white ${featured ? "mt-5 text-2xl" : "mt-5 text-xl"}`}>{item.title}</h3>
                    <p className={`text-slate-600 dark:text-slate-300 ${featured ? "mt-2.5 text-[15px] leading-7" : "mt-3 text-sm leading-7"}`}>{item.text}</p>
                    <a
                      className={`mt-5 inline-flex items-center gap-2 text-sm font-black text-brand transition hover:gap-3 dark:text-brand-200`}
                      href={wide ? "#pricing" : SIGNUP_URL}
                      aria-label={`${locale === "es" ? "Saber más sobre" : "Learn more about"} ${item.title}`}
                    >
                      {locale === "es" ? "Saber más" : "Learn more"}
                      <ArrowUpRight size={16} aria-hidden="true" />
                    </a>
                  </div>

                  {/* mini-visual decorativo en cards destacadas */}
                  {featured && (
                    <div className="mt-6 flex-1" aria-hidden="true">
                      {index === 0 ? (
                        <div className="grid gap-2 rounded-2xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-sm dark:border-white/[0.06] dark:bg-white/[0.04]">
                          {[0, 1, 2].map((row) => (
                            <div key={row} className="flex items-center gap-2.5">
                              <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-brand/70" />
                              <span className="h-2 rounded-full bg-slate-200 dark:bg-white/10" style={{ width: `${68 - row * 14}%` }} />
                              <ArrowRight size={12} className="ml-auto shrink-0 text-slate-300 dark:text-slate-600" />
                              <span className="h-2 w-10 shrink-0 rounded-full bg-brand/15 dark:bg-brand-400/20" />
                            </div>
                          ))}
                        </div>
                      ) : (
                        <div className="flex items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-white/60 p-4 backdrop-blur-sm dark:border-white/[0.06] dark:bg-white/[0.04]">
                          <Sparkles size={14} className="shrink-0 text-brand dark:text-brand-400" />
                          <span className="font-mono text-[11px] tracking-tight text-slate-500 dark:text-slate-400">
                            {locale === "es" ? "> genera 5 historias INVEST del insumo #12" : "> generate 5 INVEST stories from input #12"}
                          </span>
                          <span className="ml-auto h-4 w-1.5 shrink-0 animate-pulse rounded-full bg-brand/60" />
                        </div>
                      )}
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
