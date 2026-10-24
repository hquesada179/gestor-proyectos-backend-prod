import { ChevronDown } from "lucide-react";
import { motion, useReducedMotion } from "motion/react";
import { useState } from "react";
import { content, type Locale } from "../data/content";
import { SectionHeading } from "./SectionHeading";

export function FAQ({ locale }: { locale: Locale }) {
  const copy = content[locale].faq;
  const [open, setOpen] = useState(0);
  const prefersReduced = useReducedMotion();

  return (
    <section id="faq" className="py-24 md:py-28">
      <div className="mx-auto max-w-2xl px-5 sm:px-6 lg:px-8">
        <SectionHeading eyebrow={copy.eyebrow} title={copy.title} accent={copy.accent} subtitle={copy.subtitle} />
        <div className="grid gap-3">
          {copy.items.map((item, index) => {
            const isOpen = open === index;
            return (
              <div key={item.question} className="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
                <h3>
                  <button
                    type="button"
                    className="flex w-full items-center justify-between gap-4 px-5 py-5 text-left font-black text-slate-950 dark:text-white sm:px-6"
                    aria-expanded={isOpen}
                    aria-controls={`faq-${index}`}
                    onClick={() => setOpen(isOpen ? -1 : index)}
                  >
                    <span>{item.question}</span>
                    <ChevronDown
                      aria-hidden="true"
                      className={`shrink-0 text-brand transition-transform duration-300 ${isOpen ? "rotate-180" : ""}`}
                      size={20}
                    />
                  </button>
                </h3>
                <motion.div
                  id={`faq-${index}`}
                  initial={false}
                  animate={{
                    height: isOpen ? "auto" : 0,
                    opacity: isOpen ? 1 : 0,
                  }}
                  transition={{
                    duration: prefersReduced ? 0 : 0.35,
                    ease: [0.22, 1, 0.36, 1],
                  }}
                  className="overflow-hidden"
                  aria-hidden={!isOpen}
                >
                  <p className="px-5 pb-5 text-sm leading-7 text-slate-600 dark:text-slate-300 sm:px-6">{item.answer}</p>
                </motion.div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
