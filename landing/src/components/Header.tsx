import { ArrowRight, ChevronDown, Globe2, Menu, Moon, Sun, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { SIGNUP_URL, type Locale, type Theme } from "../data/content";

type HeaderProps = {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  theme: Theme;
  setTheme: (theme: Theme) => void;
  isScrolled: boolean;
  menuOpen: boolean;
  setMenuOpen: (open: boolean) => void;
  labels: {
    features: string;
    how: string;
    pricing: string;
    resources: string;
    signIn: string;
    cta: string;
    language: string;
    theme: string;
    openMenu: string;
    closeMenu: string;
  };
};

const navLinks = [
  ["#features", "features"],
  ["#how", "how"],
  ["#pricing", "pricing"],
  ["#faq", "resources"],
] as const;

export function Header({
  locale,
  setLocale,
  theme,
  setTheme,
  isScrolled,
  menuOpen,
  setMenuOpen,
  labels,
}: HeaderProps) {
  const nextTheme = theme === "dark" ? "light" : "dark";
  const drawerRef = useRef<HTMLElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const closeRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    if (!menuOpen) return;
    const drawer = drawerRef.current;
    if (!drawer) return;
    const prevActive = document.activeElement as HTMLElement | null;
    // focus close button on open
    window.requestAnimationFrame(() => closeRef.current?.focus());
    const getFocusable = () =>
      Array.from(drawer.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter(
        (el) => el.offsetParent !== null || el.getAttribute("aria-hidden") !== "true",
      );
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Tab") {
        const focusable = getFocusable();
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
      if (e.key === "Escape") setMenuOpen(false);
    };
    drawer.addEventListener("keydown", onKeyDown);
    return () => {
      drawer.removeEventListener("keydown", onKeyDown);
      // return focus to trigger
      if (prevActive && triggerRef.current) {
        triggerRef.current.focus();
      } else {
        triggerRef.current?.focus();
      }
    };
  }, [menuOpen, setMenuOpen]);

  return (
    <>
      <header
        className={`sticky top-0 z-40 border-b transition-all duration-300 ${
          isScrolled
            ? "border-slate-200/80 bg-white/86 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/82"
            : "border-transparent bg-white/74 backdrop-blur-xl dark:bg-slate-950/64"
        }`}
      >
        <nav className="mx-auto flex h-[76px] max-w-7xl items-center justify-between gap-4 px-5 sm:px-6 lg:px-8">
          <a href="#main" className="group flex items-center gap-2.5 font-extrabold text-slate-950 dark:text-white" aria-label="Scrumter — Inicio">
            <img className="h-8 w-8 rounded-lg object-contain" src="/scrumter-logo.png" alt="Scrumter" width={32} height={32} loading="eager" decoding="async" />
            <span className="text-[1.22rem] tracking-[-0.02em]">Scrumter</span>
          </a>

          <div className="nav-menu-desktop items-center gap-[30px]" aria-label="Principal">
            {navLinks.map(([href, key]) => (
              <a
                key={href}
                href={href}
                className="nav-link relative text-[0.92rem] font-medium text-slate-500 transition hover:text-brand dark:text-slate-300 dark:hover:text-white"
              >
                {labels[key]}
              </a>
            ))}
          </div>

          <div className="nav-cta-desktop items-center gap-3">
            <div className="nav-utils" role="toolbar" aria-label={labels.language}>
              <LangDropdown locale={locale} setLocale={setLocale} label={labels.language} />
              <span className="nav-div" aria-hidden="true" />
              <ThemeSwitchCompact theme={theme} onToggle={() => setTheme(nextTheme)} label={labels.theme} />
            </div>
            <a className="px-2 text-[0.92rem] font-semibold text-slate-600 hover:text-brand dark:text-slate-200" href={SIGNUP_URL}>
              {labels.signIn}
            </a>
            <a className="btn-primary !gap-3 !py-[11px] !pl-5 !pr-3 text-[0.88rem]" href={SIGNUP_URL}>
              <span>{labels.cta}</span>
              <span className="grid h-[26px] w-[26px] place-items-center rounded-full bg-white text-brand transition group-hover:translate-x-0.5" aria-hidden="true">
                <ArrowRight size={14} strokeWidth={2.5} />
              </span>
            </a>
          </div>

          <button
            ref={triggerRef}
            type="button"
            className="burger icon-button"
            onClick={() => setMenuOpen(true)}
            aria-label={labels.openMenu}
            aria-expanded={menuOpen}
            aria-controls="mobile-menu"
          >
            <Menu size={21} />
          </button>
        </nav>
      </header>

      <div
        className={`fixed inset-0 z-50 bg-slate-950/45 backdrop-blur-sm transition min-[821px]:hidden ${
          menuOpen ? "opacity-100" : "pointer-events-none opacity-0"
        }`}
        onClick={() => setMenuOpen(false)}
        aria-hidden="true"
      />

      <aside
        ref={drawerRef}
        id="mobile-menu"
        role="dialog"
        aria-modal="true"
        aria-label={labels.openMenu}
        className={`fixed right-0 top-0 z-50 flex h-dvh w-[min(360px,88vw)] flex-col border-l border-slate-200 bg-white p-5 shadow-2xl transition-transform duration-300 min-[821px]:hidden dark:border-white/10 dark:bg-slate-950 ${
          menuOpen ? "translate-x-0" : "translate-x-full"
        }`}
      >
        <div className="flex items-center justify-between">
          <a href="#main" className="flex items-center gap-3 font-black text-slate-950 dark:text-white" aria-label="Scrumter — Inicio">
            <img className="h-9 w-9 rounded-xl object-contain" src="/scrumter-logo.png" alt="Scrumter" width={32} height={32} loading="lazy" decoding="async" />
            Scrumter
          </a>
          <button ref={closeRef} type="button" className="icon-button" onClick={() => setMenuOpen(false)} aria-label={labels.closeMenu}>
            <X size={20} />
          </button>
        </div>

        <div className="mt-8 grid gap-2">
          {navLinks.map(([href, key]) => (
            <a
              key={href}
              href={href}
              onClick={() => setMenuOpen(false)}
              className="rounded-2xl px-4 py-3 font-bold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-white/8"
            >
              {labels[key]}
            </a>
          ))}
        </div>

        <div className="mt-7 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
          <div className="flex items-center justify-between gap-3">
            <span className="flex items-center gap-2 text-sm font-bold text-slate-600 dark:text-slate-300">
              <Globe2 size={17} />
              {labels.language}
            </span>
            <LangDropdown locale={locale} setLocale={setLocale} label={labels.language} />
          </div>
          <div className="mt-4 flex items-center justify-between gap-3">
            <span className="text-sm font-bold text-slate-600 dark:text-slate-300">{labels.theme}</span>
            <ThemeSwitchCompact theme={theme} onToggle={() => setTheme(nextTheme)} label={labels.theme} />
          </div>
        </div>

        <div className="mt-auto grid gap-3 pt-6">
          <a className="btn-primary justify-center !gap-3" href={SIGNUP_URL} onClick={() => setMenuOpen(false)}>
            <span>{labels.cta}</span>
            <span className="grid h-[26px] w-[26px] place-items-center rounded-full bg-white text-brand" aria-hidden="true">
              <ArrowRight size={14} strokeWidth={2.5} />
            </span>
          </a>
          <a className="text-center text-sm font-bold text-slate-600 dark:text-slate-300" href={SIGNUP_URL} onClick={() => setMenuOpen(false)}>
            {labels.signIn}
          </a>
        </div>
      </aside>
    </>
  );
}

function LangDropdown({
  locale,
  setLocale,
  label,
}: {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  label: string;
}) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const onPointerDown = (event: PointerEvent) => {
      if (ref.current && !ref.current.contains(event.target as Node)) setOpen(false);
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };
    document.addEventListener("pointerdown", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("pointerdown", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open]);

  const flag = locale === "es" ? "🇪🇸" : "🇺🇸";

  return (
    <div ref={ref} className="lang-dd">
      <button
        type="button"
        className="lang-dd-trigger"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={`${label}: ${locale.toUpperCase()}`}
        onClick={() => setOpen((value) => !value)}
      >
        <span aria-hidden="true">{flag}</span>
        <span>{locale.toUpperCase()}</span>
        <ChevronDown size={13} className="opacity-55 transition group-hover:opacity-100" aria-hidden="true" />
      </button>
      <ul role="menu" aria-label={label} className={`lang-dd-menu ${open ? "open" : ""}`}>
        {(["es", "en"] as Locale[]).map((option) => (
          <li key={option} role="none">
            <button
              type="button"
              role="menuitem"
              aria-selected={locale === option}
              onClick={() => {
                setLocale(option);
                setOpen(false);
              }}
              className={`lang-dd-item ${locale === option ? "active" : ""}`}
            >
              {option === "es" ? "🇪🇸 Español" : "🇺🇸 English"}
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}

function ThemeSwitchCompact({
  theme,
  onToggle,
  label,
}: {
  theme: Theme;
  onToggle: () => void;
  label: string;
}) {
  const isDark = theme === "dark";
  return (
    <button type="button" className="theme-switch-compact" onClick={onToggle} aria-pressed={isDark} aria-label={label}>
      <Sun size={11} className="tsc-icon" aria-hidden="true" />
      <Moon size={11} className="tsc-icon" aria-hidden="true" />
      <span className="tsc-knob" aria-hidden="true">
        {isDark ? <Moon size={10} /> : <Sun size={10} />}
      </span>
    </button>
  );
}
