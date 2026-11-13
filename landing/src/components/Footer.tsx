import { content, type Locale } from "../data/content";

export function Footer({ locale }: { locale: Locale }) {
  const copy = content[locale].footer;
  const navCopy = content[locale].nav;

  return (
    <footer className="border-t border-slate-200 bg-white py-12 dark:border-white/10 dark:bg-slate-950">
      <div className="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <div className="grid gap-10 lg:grid-cols-[1.2fr_1fr]">
          <div>
            <a href="#hero" className="inline-flex items-center gap-3 font-black text-slate-950 dark:text-white" aria-label={navCopy.homeAria}>
              <img className="h-10 w-10 rounded-xl object-contain" src="/scrumter-logo.png" alt="Scrumter" width={40} height={40} loading="lazy" decoding="async" />
              <span className="text-xl">Scrumter</span>
            </a>
            <p className="mt-4 max-w-sm text-sm leading-7 text-slate-600 dark:text-slate-300">{copy.description}</p>
          </div>

          <div className="grid grid-cols-2 gap-8">
            {copy.columns.map((column) => (
              <nav key={column.title} aria-label={column.title}>
                <b className="text-sm text-slate-950 dark:text-white">{column.title}</b>
                <ul className="mt-4 grid gap-2.5">
                  {column.links.map((link) => (
                    <li key={link.label}>
                      <a
                        href={link.href}
                        className="inline-flex min-h-8 items-center text-sm font-semibold text-slate-600 transition hover:text-brand dark:text-slate-300 dark:hover:text-brand-300"
                      >
                        {link.label}
                      </a>
                    </li>
                  ))}
                </ul>
              </nav>
            ))}
          </div>
        </div>

        {/* Reserva margen derecho en desktop para que el botón flotante de volver arriba no tape el texto */}
        <div className="mt-10 flex flex-col gap-3 border-t border-slate-200 pt-6 text-sm font-semibold text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:pr-14 dark:border-white/10 dark:text-slate-300">
          <span>{copy.rights}</span>
          <span>{copy.made}</span>
        </div>
      </div>
    </footer>
  );
}
