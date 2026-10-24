import { Star } from "lucide-react";
import { content, type Locale } from "../data/content";
import { Reveal } from "./Reveal";
import { SectionHeading } from "./SectionHeading";

export function Testimonials({ locale }: { locale: Locale }) {
  const copy = content[locale].testimonials;

  return (
    <section className="border-y border-slate-200 bg-brand-50 py-24 text-slate-950 md:py-28 dark:border-white/10 dark:bg-slate-950 dark:text-white">
      <div className="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <div className="mx-auto mb-12 max-w-3xl text-center md:mb-16">
          <span className="pill mx-auto !font-mono !text-[10.5px] !font-medium !tracking-[0.14em] uppercase border-brand/15 bg-white text-brand dark:border-white/10 dark:bg-white/10 dark:text-indigo-100">{copy.eyebrow}</span>
          <h2 className="mt-5 text-balance text-3xl font-black md:text-5xl">
            {copy.title} <span className="text-brand dark:text-brand-200">{copy.accent}</span>
          </h2>
          <p className="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300">{copy.subtitle}</p>
        </div>

        <div className="grid gap-5 md:grid-cols-3">
          {copy.items.map((item, index) => (
            <Reveal key={item.name} delay={index * 80}>
              <article className="h-full rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/[0.06] dark:shadow-2xl dark:shadow-black/10">
                <div className="flex gap-1 text-amber-500 dark:text-amber-300" aria-label={locale === "es" ? "5 estrellas" : "5 stars"} aria-hidden="true">
                  {Array.from({ length: 5 }).map((_, starIndex) => (
                    <Star key={starIndex} size={17} fill="currentColor" aria-hidden="true" />
                  ))}
                </div>
                <blockquote className="mt-6">
                  <p className="text-base font-semibold leading-8 text-slate-700 dark:text-slate-100">“{item.quote}”</p>
                  <footer className="mt-8 flex items-center gap-3">
                    <span className="grid h-11 w-11 place-items-center rounded-full bg-brand-100 text-sm font-black text-brand dark:bg-white dark:text-brand" aria-hidden="true">
                      {item.initials}
                    </span>
                    <cite className="not-italic">
                      <b className="block text-sm not-italic">{item.name}</b>
                      <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">{item.role}</span>
                    </cite>
                  </footer>
                </blockquote>
              </article>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
