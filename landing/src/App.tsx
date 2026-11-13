import { ArrowUp } from "lucide-react";
import { useReducedMotion, useScroll } from "motion/react";
import { useEffect, useMemo, useState } from "react";
import { CTA } from "./components/CTA";
import { FAQ } from "./components/FAQ";
import { Features } from "./components/Features";
import { Footer } from "./components/Footer";
import { Header } from "./components/Header";
import { Hero } from "./components/Hero";
import { HowItWorks } from "./components/HowItWorks";
import { Pricing } from "./components/Pricing";
import { ScaleSection } from "./components/ScaleSection";
import { Testimonials } from "./components/Testimonials";
import { TrustLogos } from "./components/TrustLogos";
import { content, type Currency, type Locale, type Theme } from "./data/content";

function getInitialLocale(): Locale {
  // Permite enlaces compartibles con ?lang=es|en sin perder preferencia previa si falta el parámetro.
  const queryLang = new URLSearchParams(window.location.search).get("lang")?.toLowerCase();
  if (queryLang === "es" || queryLang === "en") return queryLang;
  const stored = localStorage.getItem("scrumter-lang");
  return stored === "en" || stored === "es" ? stored : "es";
}

function getInitialTheme(): Theme {
  const stored = localStorage.getItem("scrumter-theme");
  if (stored === "dark" || stored === "light") return stored;
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

export default function App() {
  const [locale, setLocale] = useState<Locale>(getInitialLocale);
  const [theme, setTheme] = useState<Theme>(getInitialTheme);
  const [currency, setCurrency] = useState<Currency>("cop");
  const [menuOpen, setMenuOpen] = useState(false);
  const [scrollState, setScrollState] = useState({ scrolled: false, progress: 0, showTop: false });
  const labels = useMemo(() => content[locale].nav, [locale]);
  const prefersReduced = useReducedMotion();
  const { scrollY, scrollYProgress } = useScroll();

  useEffect(() => {
    document.documentElement.lang = locale;
    localStorage.setItem("scrumter-lang", locale);
  }, [locale]);

  useEffect(() => {
    document.documentElement.classList.toggle("dark", theme === "dark");
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("scrumter-theme", theme);
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute("content", theme === "dark" ? "#0b1626" : "#3626ce");
  }, [theme]);

  useEffect(() => {
    document.body.classList.toggle("overflow-hidden", menuOpen);
    const shell = document.getElementById("page-shell") as (HTMLElement & { inert?: boolean }) | null;
    if (shell) {
      if (menuOpen) shell.setAttribute("inert", "");
      else shell.removeAttribute("inert");
      if ("inert" in shell) shell.inert = menuOpen;
    }
    return () => {
      document.body.classList.remove("overflow-hidden");
      const currentShell = document.getElementById("page-shell") as (HTMLElement & { inert?: boolean }) | null;
      if (currentShell) {
        currentShell.removeAttribute("inert");
        if ("inert" in currentShell) currentShell.inert = false;
      }
    };
  }, [menuOpen]);

  useEffect(() => {
    const unsubY = scrollY.on("change", (y) => {
      setScrollState((prev) => ({
        ...prev,
        scrolled: y > 12,
        showTop: y > 620,
      }));
    });
    const unsubProgress = scrollYProgress.on("change", (progress) => {
      setScrollState((prev) => ({ ...prev, progress }));
    });
    return () => {
      unsubY();
      unsubProgress();
    };
  }, [scrollY, scrollYProgress]);

  const scrollToTop = () => {
    const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    window.scrollTo({ top: 0, behavior: reduced ? "auto" : "smooth" });
  };

  const toTopVisible = scrollState.showTop && !menuOpen;

  return (
    <div className="min-h-screen bg-white text-slate-950 antialiased dark:bg-slate-950 dark:text-white">
      <a className="skip-link" href="#main-content">
        {labels.skipToContent}
      </a>
      <div
        className="fixed left-0 top-0 z-[60] h-1 w-full origin-left bg-gradient-to-r from-brand via-brand-400 to-brand-500"
        style={{
          transform: `scaleX(${scrollState.progress})`,
          transition: prefersReduced ? "none" : "transform 300ms cubic-bezier(0.22, 1, 0.36, 1)",
        }}
        aria-hidden="true"
      />

      <Header
        locale={locale}
        setLocale={setLocale}
        theme={theme}
        setTheme={setTheme}
        isScrolled={scrollState.scrolled}
        menuOpen={menuOpen}
        setMenuOpen={setMenuOpen}
        labels={labels}
      />

      {/* Aísla el contenido principal y pie de página cuando el cajón móvil está abierto. */}
      <div id="page-shell">
        <main id="main-content" tabIndex={-1}>
          <Hero locale={locale} />
          <TrustLogos locale={locale} />
          <HowItWorks locale={locale} />
          <Features locale={locale} />
          <ScaleSection locale={locale} />
          <Testimonials locale={locale} />
          <Pricing locale={locale} currency={currency} setCurrency={setCurrency} />
          <FAQ locale={locale} />
          <CTA locale={locale} />
        </main>
        <Footer locale={locale} />
      </div>

      <button
        type="button"
        tabIndex={toTopVisible ? 0 : -1}
        aria-hidden={!toTopVisible}
        className={`fixed bottom-5 right-5 z-40 grid h-12 w-12 place-items-center rounded-full bg-brand text-white shadow-xl shadow-brand/30 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-950 ${
          toTopVisible ? "translate-y-0 opacity-100" : "pointer-events-none translate-y-3 opacity-0"
        }`}
        onClick={scrollToTop}
        aria-label={labels.toTop}
      >
        <ArrowUp size={20} aria-hidden="true" />
      </button>
    </div>
  );
}
