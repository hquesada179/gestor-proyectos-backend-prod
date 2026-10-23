import { content, iconMap, type Locale } from "../data/content";
import { Reveal } from "./Reveal";
import { SectionHeading } from "./SectionHeading";

export function HowItWorks({ locale }: { locale: Locale }) {
  const copy = content[locale].how;

  return (
    <section id="how" className="py-24 md:py-28">
      <div className="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <SectionHeading eyebrow={copy.eyebrow} title={copy.title} accent={copy.accent} subtitle={copy.subtitle} />
        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          {copy.steps.map((step, index) => {
            const Icon = iconMap[step.icon];
            return (
              <Reveal key={step.title} delay={index * 70}>
                <article className="feature-card h-full">
                  <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand/10 text-brand dark:bg-brand-400/12 dark:text-brand-200">
                    <Icon size={23} />
                  </div>
                  <span className="mt-6 inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-950 text-xs font-black text-white dark:bg-white dark:text-slate-950">
                    {index + 1}
                  </span>
                  <h3 className="mt-4 text-xl font-black text-slate-950 dark:text-white">{step.title}</h3>
                  <p className="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{step.text}</p>
                </article>
              </Reveal>
            );
          })}
        </div>
      </div>
    </section>
  );
}
