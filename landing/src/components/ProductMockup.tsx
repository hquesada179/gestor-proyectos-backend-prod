import {
  Activity,
  ArrowRight,
  Bell,
  Bot,
  CalendarDays,
  CheckCircle2,
  ClipboardList,
  CreditCard,
  FileInput,
  FolderKanban,
  KanbanSquare,
  LayoutDashboard,
  ListChecks,
  ListTodo,
  LogOut,
  MessageSquare,
  Plus,
  Sparkles,
  User,
} from "lucide-react";
import { content, type Locale } from "../data/content";

const sectionIcons = [
  [LayoutDashboard, FolderKanban, ListChecks],
  [ClipboardList, KanbanSquare, CalendarDays, ListTodo, FileInput, CalendarDays],
  [Bot, CreditCard],
];

const statsTones = ["brand", "amber", "emerald"] as const;
const modulesTones = ["brand", "amber", "violet", "orange"] as const;

const toneClasses = {
  brand: "bg-brand/10 text-brand dark:bg-brand-400/15 dark:text-brand-200",
  amber: "bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-200",
  emerald: "bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200",
  violet: "bg-violet-100 text-violet-800 dark:bg-violet-400/15 dark:text-violet-200",
  orange: "bg-orange-100 text-orange-800 dark:bg-orange-400/15 dark:text-orange-200",
} as const;

export function ProductMockup({ locale }: { locale: Locale }) {
  const copy = content[locale].mockup;

  return (
    <div
      className="relative mx-auto w-full max-w-full overflow-hidden rounded-[18px] border border-slate-200 bg-white shadow-[0_34px_80px_rgba(2,6,23,0.18)] dark:border-white/[0.08] dark:bg-[#0c1a2e]"
    >
      {/* Descripción accesible única para evitar landmarks internos y encabezados fuera de orden. */}
      <p className="sr-only">{copy.ariaLabel}</p>

      <div className="grid min-h-[480px] md:grid-cols-[220px_1fr]" aria-hidden="true">
        {/* Sidebar decorativo */}
        <div className="hidden min-w-0 border-r border-slate-200 bg-slate-50 p-3 md:block dark:border-white/[0.06] dark:bg-[#0f1f33]">
          <div className="mb-5 flex items-center gap-2 px-2 font-black text-slate-950 dark:text-white">
            <img className="h-6 w-6 rounded-lg object-contain" src="/landing/scrumter-logo.png" alt="" width={24} height={24} loading="lazy" decoding="async" />
            <span>Scrumter</span>
          </div>

          {copy.sidebarSections.map((section, sectionIndex) => (
            <div key={section.label ?? "principal"} className="mb-4">
              {section.label && (
                <p className="mb-1 px-2 text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">
                  {section.label}
                </p>
              )}
              <div className="grid gap-0.5">
                {section.items.map((label, itemIndex) => {
                  const Icon = sectionIcons[sectionIndex][itemIndex];
                  const active = sectionIndex === 0 && itemIndex === 0;
                  return (
                    <span
                      key={label}
                      className={`flex min-w-0 items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-bold ${
                        active ? "bg-brand text-white shadow-sm" : "text-slate-600 dark:text-slate-300"
                      }`}
                    >
                      <Icon size={15} className="shrink-0" />
                      <span className="truncate">{label}</span>
                    </span>
                  );
                })}
              </div>
            </div>
          ))}

          <div className="mt-5 grid gap-2">
            <span className="flex items-center justify-center gap-2 rounded-xl bg-brand py-2.5 text-[13px] font-black text-white">
              <Plus size={15} />
              {copy.newProject}
            </span>
            <span className="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-bold text-slate-600 dark:text-slate-300">
              <User size={15} />
              {copy.profile}
            </span>
            <span className="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-bold text-slate-600 dark:text-slate-300">
              <LogOut size={15} />
              {copy.signOut}
            </span>
          </div>
        </div>

        {/* Lienzo del panel simulado */}
        <div className="min-w-0 bg-white p-4 sm:p-5 dark:bg-[#0c1a2e]">
          <div className="flex items-center gap-3 border-b border-slate-200 pb-4 dark:border-white/[0.06]">
            <div className="mr-auto">
              <p className="text-lg font-black text-slate-950 dark:text-white">{copy.dashboard}</p>
            </div>
            <span className="grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
              <Bell size={17} />
            </span>
            <span className="grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
              <MessageSquare size={17} />
            </span>
            <span className="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-amber-500 to-rose-600 text-xs font-black text-white">
              SM
            </span>
          </div>

          {/* Resumen de acción rápida */}
          <div className="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/[0.06] dark:bg-[#122a4a]">
            <div className="min-w-0">
              <p className="text-base font-black text-slate-950 dark:text-white">{copy.welcome}</p>
              <p className="mt-1 text-xs font-medium leading-relaxed text-slate-600 dark:text-slate-300">{copy.welcomeText}</p>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              <span className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                <Sparkles size={14} className="shrink-0 text-brand dark:text-brand-300" />
                {copy.createWithAI}
              </span>
              <span className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-brand px-3 py-1.5 text-xs font-black text-white">
                <Plus size={14} className="shrink-0" />
                {copy.newProject}
              </span>
            </div>
          </div>

          {/* Indicadores con estado activo real */}
          <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            {copy.statsLabels.map((label, index) => {
              const Icon = statsTones[index] === "brand" ? FolderKanban : statsTones[index] === "amber" ? ListChecks : Activity;
              return (
                <div key={label} className="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/[0.06] dark:bg-white/[0.04]">
                  <div className={`flex h-9 w-9 items-center justify-center rounded-xl ${toneClasses[statsTones[index]]}`}>
                    <Icon size={17} />
                  </div>
                  <b className="mt-4 block text-3xl font-black tabular-nums text-slate-950 dark:text-white">{copy.statsValues[index]}</b>
                  <span className="mt-1 block text-xs font-bold leading-tight text-slate-600 dark:text-slate-300">{label}</span>
                </div>
              );
            })}
          </div>

          {/* Una columna en móvil estrecho y rejilla 2x2 desde sm para mostrar módulos sin recorte ni invasión horizontal. */}
          <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            {copy.modulesLabels.map((label, index) => {
              const Icon = modulesTones[index] === "brand" ? CalendarDays : modulesTones[index] === "amber" ? KanbanSquare : modulesTones[index] === "violet" ? ClipboardList : FileInput;
              return (
                <div
                  key={label}
                  className="flex min-w-0 items-center gap-2.5 rounded-2xl border border-slate-200 bg-slate-50 px-2.5 py-2.5 text-left transition hover:-translate-y-0.5 dark:border-white/[0.06] dark:bg-white/[0.03]"
                >
                  <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ${toneClasses[modulesTones[index]]}`}>
                    <Icon size={16} />
                  </span>
                  <span className="min-w-0 text-xs font-bold leading-snug text-slate-700 dark:text-slate-200">
                    {label}
                  </span>
                </div>
              );
            })}
          </div>

          {/* Sprint activo en curso */}
          <div className="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50/90 p-3.5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-white/[0.03]">
            <div className="flex min-w-0 items-center gap-2.5">
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                <CheckCircle2 size={16} />
              </span>
              <p className="text-xs font-bold leading-snug text-slate-700 dark:text-slate-200">{copy.recentTitle}</p>
            </div>
            <span className="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap text-xs font-black text-brand dark:text-brand-300">
              {copy.recentCta}
              <ArrowRight size={13} />
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}
