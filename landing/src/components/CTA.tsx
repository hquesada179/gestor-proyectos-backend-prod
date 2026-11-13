import { ArrowRight, Rocket } from "lucide-react";
import { SIGNUP_URL, content, type Locale } from "../data/content";

export function CTA({ locale }: { locale: Locale }) {
  const copy = content[locale].cta;

  return (
    <section id="cta" className="px-5 pb-20 pt-12 md:pt-16 sm:px-6 lg:px-8">
      <div className="relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand via-[#4f46e5] to-[#7c3aed] px-6 py-14 text-center text-white shadow-2xl shadow-brand/30 sm:px-10 md:py-20 dark:from-[#1e1b4b] dark:via-[#4c1d95] dark:to-[#6d28d9] dark:shadow-black/40">
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(600px_circle_at_50%_110%,rgba(255,255,255,0.14),transparent_65%)]" aria-hidden="true" />
        <div className="hero-noise pointer-events-none absolute inset-0 !opacity-[0.05]" aria-hidden="true" />

        <div className="relative mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white/20 text-white shadow-lg shadow-black/10 ring-1 ring-white/30 backdrop-blur-sm">
          <Rocket size={28} aria-hidden="true" />
        </div>

        <h2 className="relative mx-auto mt-6 max-w-3xl text-balance text-3xl font-black md:text-5xl">{copy.title}</h2>
        <p className="relative mx-auto mt-4 max-w-2xl text-base leading-8 text-indigo-100">{copy.subtitle}</p>

        {/* Acciones directas a registro y precios sin capturar datos descartados en el formulario. */}
        <div className="relative mt-8 flex flex-wrap items-center justify-center gap-3.5">
          <a
            href={SIGNUP_URL}
            className="group inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-white px-7 py-3 text-sm font-black text-brand shadow-lg shadow-black/15 transition hover:-translate-y-0.5 hover:bg-indigo-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand"
          >
            {copy.primary}
            <ArrowRight size={17} className="transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
          </a>
          <a
            href="#pricing"
            className="inline-flex min-h-12 items-center justify-center rounded-full border border-white/35 bg-white/10 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand"
          >
            {copy.secondary}
          </a>
        </div>

        <p className="relative mt-5 text-sm font-semibold text-indigo-100">{copy.note}</p>
      </div>
    </section>
  );
}
