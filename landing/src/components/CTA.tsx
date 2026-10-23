import { ArrowRight, Loader2, Mail } from "lucide-react";
import { useId, useState, type FormEvent } from "react";
import { SIGNUP_URL, content, type Locale } from "../data/content";

export function CTA({ locale }: { locale: Locale }) {
  const copy = content[locale].cta;
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [messageType, setMessageType] = useState<"error" | "success" | "">("");
  const [loading, setLoading] = useState(false);
  const inputId = useId();
  const helpId = `${inputId}-help`;
  const msgId = `${inputId}-msg`;

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (loading) return;
    const trimmed = email.trim();
    const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed);
    if (!valid) {
      setMessage(copy.error);
      setMessageType("error");
      return;
    }
    setLoading(true);
    setMessage("");
    setMessageType("");
    // Lleva al usuario a la app tras validar el email
    window.setTimeout(() => {
      window.location.href = SIGNUP_URL;
    }, 400);
  }

  return (
    // 1. pt-4 cambiado a pt-12 md:pt-16 para dar aire superior
    <section id="cta" className="px-5 pb-20 pt-12 md:pt-16 sm:px-6 lg:px-8">
      <div className="relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand via-[#4f46e5] to-[#7c3aed] px-6 py-14 text-center text-white shadow-2xl shadow-brand/30 sm:px-10 md:py-20 dark:from-[#1e1b4b] dark:via-[#4c1d95] dark:to-[#6d28d9] dark:shadow-black/40">
        {/* spotlight + ruido */}
        <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(600px_circle_at_50%_110%,rgba(255,255,255,0.14),transparent_65%)]" aria-hidden="true" />
        <div className="hero-noise pointer-events-none absolute inset-0 !opacity-[0.05]" aria-hidden="true" />
        
        {/* 2. Icono más grande (h-16) y con efecto glassmorphism sutil */}
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white/20 text-white shadow-lg shadow-black/10 backdrop-blur-sm ring-1 ring-white/30">
          <Mail size={28} aria-hidden="true" />
        </div>
        
        <h2 className="mx-auto mt-6 max-w-3xl text-balance text-3xl font-black md:text-5xl">{copy.title}</h2>
        <p className="mx-auto mt-4 max-w-2xl text-base leading-8 text-indigo-100">{copy.subtitle}</p>

        {/* 3. Form con más padding (p-3 sm:p-4) y sombra interna */}
        <form className="mx-auto mt-8 flex max-w-2xl flex-col gap-3 rounded-3xl bg-white p-3 shadow-xl shadow-black/10 sm:flex-row sm:p-4" noValidate onSubmit={onSubmit} aria-busy={loading}>
          <label htmlFor={inputId} className="sr-only">{copy.placeholder}</label>
          
          {/* 4. Input con borde sutil y transiciones suaves */}
          <input
            id={inputId}
            className={`min-h-14 flex-1 rounded-2xl border border-slate-200 px-5 text-base font-semibold text-slate-950 outline-none transition-colors placeholder:text-slate-400 focus:border-brand focus-visible:ring-2 focus-visible:ring-brand/20 ${
              messageType === "error" ? "border-rose-400 focus:border-rose-500 ring-2 ring-rose-100" : ""
            }`}
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            placeholder={copy.placeholder}
            autoComplete="email"
            inputMode="email"
            required
            aria-invalid={messageType === "error"}
            aria-describedby={`${helpId} ${msgId}`}
            disabled={loading}
          />
          
          {/* 5. Botón con color de marca (Destaca sobre el fondo blanco) */}
          <button
            className="inline-flex min-h-14 items-center justify-center gap-2 rounded-2xl bg-brand px-6 text-sm font-black text-white shadow-lg shadow-brand/30 transition-all hover:brightness-110 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
            type="submit"
            disabled={loading}
            aria-busy={loading}
          >
            {loading ? <Loader2 size={18} className="animate-spin" aria-hidden="true" /> : <ArrowRight size={18} aria-hidden="true" />}
            {loading ? (locale === "es" ? "Enviando…" : "Sending…") : copy.button}
          </button>
        </form>

        <p id={helpId} className="sr-only">{copy.note}</p>
        
        {/* 6. Colores de mensaje con mayor contraste (300 en lugar de 200) */}
        <p
          id={msgId}
          className={`mt-4 min-h-6 text-sm font-bold transition-colors ${
            messageType === "error" ? "text-rose-300" : messageType === "success" ? "text-emerald-300" : "text-indigo-100/80"
          }`}
          role={messageType === "error" ? "alert" : "status"}
          aria-live={messageType === "error" ? "assertive" : "polite"}
        >
          {message || copy.note}
        </p>
      </div>
    </section>
  );
}
