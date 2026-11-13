import { ArrowRight, ChevronDown, Globe2, Menu, Moon, Sun, X } from "lucide-react";
import { useEffect, useRef, useState, type KeyboardEvent as ReactKeyboardEvent } from "react";
import { LOGIN_URL, SIGNUP_URL, type Locale, type Theme } from "../data/content";

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
    homeAria: string;
    mainNav: string;
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
  const skipFocusRestoreRef = useRef(false);

  useEffect(() => {
    const drawer = drawerRef.current;
    if (!drawer) return;

    // Marca el cajón cerrado como inert para impedir paradas de Tab sobre enlaces ocultos.
    if (!menuOpen) {
      drawer.setAttribute("inert", "");
      if ("inert" in drawer) (drawer as HTMLElement & { inert: boolean }).inert = true;
      return;
    }

    drawer.removeAttribute("inert");
    if ("inert" in drawer) (drawer as HTMLElement & { inert: boolean }).inert = false;
    skipFocusRestoreRef.current = false;

    // Enfoca sincrónicamente el botón de cierre al liberar inert en el cajón móvil.
    closeRef.current?.focus({ preventScroll: true });

    // Excluye elementos ocultos (como opciones del menú idioma cerrado) comprobando cajas renderizadas.
    const getFocusable = () =>
      Array.from(
        drawer.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'),
      ).filter((el) => el.getClientRects().length > 0 && getComputedStyle(el).visibility !== "hidden");

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
      if (e.key === "Escape" && !e.defaultPrevented) {
        setMenuOpen(false);
      }
    };

    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("keydown", onKeyDown);
      // Evita devolver foco al botón hamburguesa cuando el cierre vino de navegar a un ancla interna.
      if (!skipFocusRestoreRef.current) {
        triggerRef.current?.focus();
      }
    };
  }, [menuOpen, setMenuOpen]);

  function closeFromAnchor() {
    skipFocusRestoreRef.current = true;
    setMenuOpen(false);
  }

  return (
    <>
      <header
        className={`sticky top-0 z-40 border-b transition-all duration-300 ${
          isScrolled
            ? "border-slate-200/80 bg-white/86 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/82"
            : "border-transparent bg-white/74 backdrop-blur-xl dark:bg-slate-950/64"
        }`}
      >
        <nav aria-label={labels.mainNav} className="mx-auto flex h-[76px] max-w-7xl items-center justify-between gap-4 px-5 sm:px-6 lg:px-8">
          <a href="#hero" className="group flex items-center gap-2.5 font-extrabold text-slate-950 dark:text-white" aria-label={labels.homeAria}>
            <img className="h-8 w-8 rounded-lg object-contain" src="/scrumter-logo.png" alt="Scrumter" width={32} height={32} loading="eager" decoding="async" />
            <span className="text-[1.22rem] tracking-[-0.02em]">Scrumter</span>
          </a>

          <div className="nav-menu-desktop items-center gap-[30px]">
            {navLinks.map(([href, key]) => (
              <a
                key={href}
                href={href}
                className="nav-link relative text-[0.92rem] font-medium text-slate-600 transition hover:text-brand dark:text-slate-300 dark:hover:text-white"
              >
                {labels[key]}
              </a>
            ))}
          </div>

          <div className="nav-cta-desktop items-center gap-3">
            {/* Agrupación visual sin role="toolbar" porque el menú y switch manejan su propia semántica */}
            <div className="nav-utils">
              <LangDropdown locale={locale} setLocale={setLocale} label={labels.language} />
              <span className="nav-div" aria-hidden="true" />
              <ThemeSwitchCompact theme={theme} onToggle={() => setTheme(nextTheme)} label={labels.theme} />
            </div>
            <a className="px-2 text-[0.92rem] font-semibold text-slate-600 transition hover:text-brand dark:text-slate-200 dark:hover:text-white" href={LOGIN_URL}>
              {labels.signIn}
            </a>
            <a className="btn-primary group !gap-3 !py-[11px] !pl-5 !pr-3 text-[0.88rem]" href={SIGNUP_URL}>
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
            <Menu size={21} aria-hidden="true" />
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

      {/* Usa opacity-0 e inert en vez de invisible para evitar retraso heredado de visibility al enfocar el botón de cierre */}
      <aside
        ref={drawerRef}
        id="mobile-menu"
        role="dialog"
        aria-modal="true"
        aria-hidden={!menuOpen}
        aria-label={labels.openMenu}
        className={`fixed right-0 top-0 z-50 flex h-dvh w-[min(360px,88vw)] flex-col border-l border-slate-200 bg-white p-5 shadow-2xl transition-[transform,opacity] duration-300 min-[821px]:hidden dark:border-white/10 dark:bg-slate-950 ${
          menuOpen ? "opacity-100 translate-x-0" : "opacity-0 pointer-events-none translate-x-full"
        }`}
      >
        <div className="flex items-center justify-between">
          <a href="#hero" onClick={closeFromAnchor} className="flex items-center gap-3 font-black text-slate-950 dark:text-white" aria-label={labels.homeAria}>
            <img className="h-9 w-9 rounded-xl object-contain" src="/scrumter-logo.png" alt="Scrumter" width={32} height={32} loading="lazy" decoding="async" />
            Scrumter
          </a>
          <button ref={closeRef} type="button" className="icon-button" onClick={() => setMenuOpen(false)} aria-label={labels.closeMenu}>
            <X size={20} aria-hidden="true" />
          </button>
        </div>

        <div className="mt-8 grid gap-2">
          {navLinks.map(([href, key]) => (
            <a
              key={href}
              href={href}
              onClick={closeFromAnchor}
              className="rounded-2xl px-4 py-3 font-bold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-white/8"
            >
              {labels[key]}
            </a>
          ))}
        </div>

        <div className="mt-7 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
          <div className="flex items-center justify-between gap-3">
            <span className="flex items-center gap-2 text-sm font-bold text-slate-600 dark:text-slate-300">
              <Globe2 size={17} aria-hidden="true" />
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
          <a className="text-center text-sm font-bold text-slate-600 hover:text-brand dark:text-slate-300 dark:hover:text-white" href={LOGIN_URL} onClick={() => setMenuOpen(false)}>
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
  const triggerRef = useRef<HTMLButtonElement>(null);
  const optionRefs = useRef<Array<HTMLButtonElement | null>>([]);

  useEffect(() => {
    if (!open) return;

    const selectedIndex = locale === "es" ? 0 : 1;
    // Sincroniza layout de .lang-dd-menu.open para que .focus() aplique de inmediato al abrir.
    const targetOption = optionRefs.current[selectedIndex];
    void targetOption?.offsetHeight;
    targetOption?.focus({ preventScroll: true });

    const onPointerDown = (event: PointerEvent) => {
      if (ref.current && !ref.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape" && !event.defaultPrevented) {
        // Intercepta en captura para cerrar primero el selector abierto aunque el foco haya salido del ul.
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        setOpen(false);
        triggerRef.current?.focus();
      }
    };

    document.addEventListener("pointerdown", onPointerDown);
    document.addEventListener("keydown", onKeyDown, true);
    return () => {
      document.removeEventListener("pointerdown", onPointerDown);
      document.removeEventListener("keydown", onKeyDown, true);
    };
  }, [locale, open]);

  function onMenuKeyDown(event: ReactKeyboardEvent<HTMLUListElement>) {
    if (event.key === "Escape" && open) {
      // Cierra primero el selector de idioma sin propagar Escape al cajón móvil padre.
      event.preventDefault();
      event.stopPropagation();
      event.nativeEvent.stopImmediatePropagation();
      setOpen(false);
      triggerRef.current?.focus();
      return;
    }

    const options = optionRefs.current.filter(Boolean) as HTMLButtonElement[];
    if (options.length === 0) return;

    const currentIndex = options.findIndex((item) => item === document.activeElement);
    if (event.key === "ArrowDown") {
      event.preventDefault();
      options[(currentIndex + 1) % options.length].focus();
    } else if (event.key === "ArrowUp") {
      event.preventDefault();
      options[(currentIndex - 1 + options.length) % options.length].focus();
    } else if (event.key === "Home") {
      event.preventDefault();
      options[0].focus();
    } else if (event.key === "End") {
      event.preventDefault();
      options[options.length - 1].focus();
    } else if (event.key === "Tab") {
      setOpen(false);
    }
  }

  return (
    <div ref={ref} className="lang-dd">
      <button
        ref={triggerRef}
        type="button"
        className="lang-dd-trigger group"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={`${label}: ${locale.toUpperCase()}`}
        onClick={() => setOpen((value) => !value)}
      >
        {/* Icono vectorial + código de idioma para evitar banderas duplicadas tipo "ES ES" en Windows. */}
        <Globe2 size={14} className="text-brand dark:text-brand-300" aria-hidden="true" />
        <span>{locale.toUpperCase()}</span>
        <ChevronDown size={13} className="opacity-65 transition group-hover:opacity-100" aria-hidden="true" />
      </button>
      <ul
        role="menu"
        aria-label={label}
        onKeyDown={onMenuKeyDown}
        className={`lang-dd-menu ${open ? "open" : ""}`}
      >
        {(["es", "en"] as Locale[]).map((option, index) => (
          <li key={option} role="none">
            <button
              ref={(el) => {
                optionRefs.current[index] = el;
              }}
              type="button"
              role="menuitemradio"
              aria-checked={locale === option}
              tabIndex={open ? 0 : -1}
              onClick={() => {
                setLocale(option);
                setOpen(false);
                triggerRef.current?.focus();
              }}
              className={`lang-dd-item ${locale === option ? "active" : ""}`}
            >
              {option === "es" ? "Español" : "English"}
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
