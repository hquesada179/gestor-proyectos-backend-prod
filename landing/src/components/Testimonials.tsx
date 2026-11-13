import { content, type Locale } from "../data/content";
import { Reveal } from "./Reveal";
import { SectionHeading } from "./SectionHeading";

export function Testimonials({ locale }: { locale: Locale }) {
  const copy = content[locale].testimonials;

  return (
    <section className="border-y border-slate-200 bg-brand-50 py-24 text-slate-950 md:py-28 dark:border-white/10 dark:bg-slate-950 dark:text-white">
      <div className="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <SectionHeading eyebrow={copy.eyebrow} title={copy.title} accent={copy.accent} subtitle={copy.subtitle} />

        <div className="grid gap-5 md:grid-cols-3">
          {copy.items.map((item, index) => (
            <Reveal key={item.name} delay={index * 80} className="h-full">
              {/* Tarjetas por rol verificable en lugar de reseñas o estrellas ficticias. */}
              <article className="flex h-full flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/[0.06] dark:shadow-2xl dark:shadow-black/10">
                <div>
                  <div className="flex items-center gap-3">
                    <span className="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-100 text-sm font-black text-brand dark:bg-brand-400/20 dark:text-brand-200" aria-hidden="true">
                      {item.initials}
                    </span>
                    <div>
                      <h3 className="text-base font-black text-slate-950 dark:text-white">{item.name}</h3>
                      <p className="text-xs font-semibold text-slate-600 dark:text-slate-300">{item.role}</p>
                    </div>
                  </div>
                  <p className="mt-5 text-[15px] font-medium leading-7 text-slate-700 dark:text-slate-200">{item.quote}</p>
                </div>
              </article>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
